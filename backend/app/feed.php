<?php
declare(strict_types=1);

const WHITTLE_FEED_SOURCES = [
    'stratast_support' => 'Support',
    'stratast_escalations' => 'HR',
    'stratast_wp346' => 'Training',
    'stratast_requisition' => 'Requisition',
];
const WHITTLE_FEED_DEPARTMENTS = [
    'stratast_support' => 'IT Department',
    'stratast_escalations' => 'HR Department',
    'stratast_wp346' => 'LND Department',
    'stratast_requisition' => 'Requisition',
];

function whittle_feed_decode(string $body): array {
    try { $data = json_decode($body, true, 64, JSON_THROW_ON_ERROR); }
    catch (JsonException $error) { throw new RuntimeException('The ticket source returned an invalid response. Try refreshing later.'); }
    if (!is_array($data) || ($data['success'] ?? false) !== true || ($data['schema_version'] ?? null) !== 1 || !isset($data['tickets']) || !is_array($data['tickets']) || ($data['count'] ?? null) !== count($data['tickets'])) {
        throw new RuntimeException('The ticket source returned an incomplete response. Try refreshing later.');
    }
    $seen = [];
    foreach ($data['tickets'] as $ticket) {
        if (!is_array($ticket) || !isset($ticket['source_key'], $ticket['id'], $ticket['external_key']) || !is_string($ticket['source_key']) || !isset(WHITTLE_FEED_SOURCES[$ticket['source_key']]) || !is_int($ticket['id']) || $ticket['id'] < 1 || $ticket['external_key'] !== $ticket['source_key'] . ':' . $ticket['id'] || isset($seen[$ticket['external_key']])) {
            throw new RuntimeException('The ticket source returned invalid ticket identifiers.');
        }
        $seen[$ticket['external_key']] = true;
        foreach (['requester', 'email', 'subject', 'status', 'priority', 'category', 'date'] as $field) {
            if (!isset($ticket[$field]) || !is_string($ticket[$field])) throw new RuntimeException('The ticket source returned invalid ticket fields.');
        }
        foreach (['assignee','assigned_department','last_update'] as $field) {
            if (isset($ticket[$field]) && !is_string($ticket[$field])) throw new RuntimeException('The ticket source returned invalid assignment or date fields.');
        }
        if (isset($ticket['thread'])) {
            if (!is_array($ticket['thread'])) throw new RuntimeException('The ticket source returned an invalid conversation.');
            foreach ($ticket['thread'] as $message) {
                if (!is_array($message) || !isset($message['body'], $message['date']) || !is_string($message['body']) || !is_string($message['date'])) throw new RuntimeException('The ticket source returned an invalid conversation.');
                foreach (['author','type','author_email','updated_at','channel'] as $field) if (isset($message[$field]) && !is_string($message[$field])) throw new RuntimeException('The ticket source returned an invalid comment author or date.');
                if (isset($message['attachment_ids']) && (!is_array($message['attachment_ids']) || count(array_filter($message['attachment_ids'], static fn($id): bool => is_int($id) && $id > 0)) !== count($message['attachment_ids']))) throw new RuntimeException('The ticket source returned invalid attachment references.');
            }
        }
        foreach (['details' => ['name','value'], 'custom_fields' => ['name','value','slug','type','raw_value'], 'attachments' => ['name','date'], 'activity' => ['body','date','author']] as $section => $required) {
            if (!isset($ticket[$section])) continue;
            if (!is_array($ticket[$section])) throw new RuntimeException('The ticket source returned invalid detail sections.');
            foreach ($ticket[$section] as $item) {
                if (!is_array($item)) throw new RuntimeException('The ticket source returned invalid detail fields.');
                foreach ($required as $field) if (!isset($item[$field]) || !is_string($item[$field])) throw new RuntimeException('The ticket source returned invalid detail fields.');
                if ($section === 'attachments' && (!isset($item['id']) || !is_int($item['id']) || $item['id'] < 1)) throw new RuntimeException('The ticket source returned invalid attachments.');
            }
        }
        if (isset($ticket['unavailable_sections']) && (!is_array($ticket['unavailable_sections']) || count(array_filter($ticket['unavailable_sections'], 'is_string')) !== count($ticket['unavailable_sections']))) throw new RuntimeException('The ticket source returned invalid availability information.');
    }
    if (isset($data['capabilities']) && (!is_array($data['capabilities']) || count(array_filter($data['capabilities'],'is_string'))!==count($data['capabilities']))) throw new RuntimeException('The ticket source returned invalid capabilities.');
    return $data;
}

function whittle_feed_fetch(?string $source = null, ?int $id = null): array {
    $configPath = __DIR__ . '/../config/feed.local.php';
    if (!is_file($configPath)) throw new RuntimeException('The external ticket connection has not been configured. Ask your administrator to upload the private feed configuration.');
    $settings = require $configPath;
    $url = is_array($settings) ? ($settings['url'] ?? '') : '';
    $token = is_array($settings) ? ($settings['token'] ?? '') : '';
    $parts = is_string($url) ? parse_url($url) : false;
    if (!$parts || ($parts['scheme'] ?? '') !== 'https' || empty($parts['host']) || isset($parts['user']) || isset($parts['pass']) || isset($parts['query']) || isset($parts['fragment']) || !is_string($token) || !preg_match('/^[a-f0-9]{64}$/D', $token)) {
        throw new RuntimeException('The external ticket connection settings are invalid. Ask your administrator to check them.');
    }
    if ($source !== null && !isset(WHITTLE_FEED_SOURCES[$source])) throw new InvalidArgumentException('Unknown ticket source.');
    if ($id !== null && ($source === null || $id < 1)) throw new InvalidArgumentException('Select a source and a valid ticket.');
    if (!function_exists('curl_init')) throw new RuntimeException('Enable the PHP cURL extension to read external tickets.');
    $params = [];
    if ($source !== null) $params['source'] = $source;
    if ($id !== null) $params['id'] = $id;
    if ($params) $url .= '?' . http_build_query($params);
    $body = '';
    $curl = curl_init($url);
    curl_setopt_array($curl, [
        CURLOPT_HTTPHEADER => ['Accept: application/json', 'X-API-Key: ' . $token],
        CURLOPT_FOLLOWLOCATION => false,
        CURLOPT_PROTOCOLS => CURLPROTO_HTTPS,
        CURLOPT_CONNECTTIMEOUT => 10,
        CURLOPT_TIMEOUT => 30,
        CURLOPT_SSL_VERIFYPEER => true,
        CURLOPT_SSL_VERIFYHOST => 2,
        CURLOPT_WRITEFUNCTION => static function ($handle, string $chunk) use (&$body): int {
            if (strlen($body) + strlen($chunk) > 8 * 1024 * 1024) return 0;
            $body .= $chunk;
            return strlen($chunk);
        },
    ]);
    $ok = curl_exec($curl);
    $status = (int) curl_getinfo($curl, CURLINFO_HTTP_CODE);
    curl_close($curl);
    if ($ok === false) throw new RuntimeException('Unable to reach the ticket source securely. Try refreshing later or ask your administrator to check the connection.');
    if (in_array($status, [401, 403], true)) throw new RuntimeException('The ticket source rejected the API token. Ask your administrator to check the private feed configuration.');
    if ($status === 404) throw new RuntimeException('This ticket is no longer available in the permitted team scope.');
    if ($status !== 200) throw new RuntimeException('The ticket source is temporarily unavailable. Try refreshing later.');
    $data = whittle_feed_decode($body);
    if ($id !== null && (count($data['tickets']) !== 1 || $data['tickets'][0]['source_key'] !== $source || $data['tickets'][0]['id'] !== $id || !isset($data['tickets'][0]['thread']))) {
        throw new RuntimeException('The ticket source returned an unexpected ticket.');
    }
    return $data;
}

function whittle_feed_text(string $body): string {
    $body = (string) preg_replace('#<(script|style)\b[^>]*>.*?</\1>#is', '', $body);
    $body = (string) preg_replace('#<br\s*/?>|</(?:p|div|li|tr|h[1-6])>#i', "\n", $body);
    return trim(html_entity_decode(strip_tags($body), ENT_QUOTES | ENT_HTML5, 'UTF-8'));
}

/** Preserve useful source formatting without source scripts, styles or embeds. */
function whittle_feed_html(string $body): string {
    if (!class_exists('DOMDocument')) return nl2br(htmlspecialchars(whittle_feed_text($body), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'));
    if (strpos($body, '<') === false) return nl2br(htmlspecialchars($body, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'));
    $document=new DOMDocument('1.0','UTF-8'); $previous=libxml_use_internal_errors(true);
    try { $document->loadHTML('<?xml encoding="UTF-8"><html><body>'.$body.'</body></html>', LIBXML_NONET); }
    finally { libxml_clear_errors(); libxml_use_internal_errors($previous); }
    $allowed=['p','br','div','span','strong','b','em','i','u','s','ul','ol','li','blockquote','pre','code','h2','h3','h4','table','thead','tbody','tfoot','tr','td','th','a','hr'];
    $remove=['script','style','iframe','object','embed','svg','math','form','input','button','textarea','select','img','video','audio','link','meta','base'];
    $clean=static function(DOMNode $parent) use (&$clean,$allowed,$remove): void {
        foreach(iterator_to_array($parent->childNodes) as $node){
            if($node instanceof DOMComment || $node instanceof DOMProcessingInstruction){$parent->removeChild($node);continue;}
            if(!($node instanceof DOMElement))continue;
            $tag=strtolower($node->tagName);
            if(in_array($tag,$remove,true)){$parent->removeChild($node);continue;}
            $href=$tag==='a'?$node->getAttribute('href'):'';
            foreach(iterator_to_array($node->attributes) as $attribute)$node->removeAttribute($attribute->name);
            if($tag==='a' && preg_match('#^(https?://|mailto:|tel:)#i',trim($href)) && !preg_match('/[\x00-\x20\x7f]/',trim($href))){$node->setAttribute('href',trim($href));$node->setAttribute('rel','noopener noreferrer');}
            $clean($node);
            if(!in_array($tag,$allowed,true)){while($node->firstChild)$parent->insertBefore($node->firstChild,$node);$parent->removeChild($node);}
        }
    };
    $root=$document->getElementsByTagName('body')->item(0);
    if(!$root)return nl2br(htmlspecialchars(whittle_feed_text($body),ENT_QUOTES | ENT_SUBSTITUTE,'UTF-8'));
    $clean($root); $html='';foreach($root->childNodes as $node)$html.=$document->saveHTML($node);
    return $html;
}
