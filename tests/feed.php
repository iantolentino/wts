<?php
declare(strict_types=1);
require dirname(__DIR__) . '/backend/app/feed.php';
$checks = 0;
function feed_check(bool $passed, string $name): void {
    global $checks;
    if (!$passed) throw new RuntimeException('FAIL: ' . $name);
    ++$checks;
    echo 'PASS: ', $name, PHP_EOL;
}
function invalid_feed(array $data): bool {
    try { whittle_feed_decode(json_encode($data, JSON_THROW_ON_ERROR)); return false; }
    catch (RuntimeException $error) { return true; }
}
$ticket = ['source_key' => 'stratast_support', 'id' => 7, 'external_key' => 'stratast_support:7', 'requester' => 'Fixture Person', 'email' => 'fixture@example.invalid', 'subject' => '<script>alert(1)</script>', 'status' => 'Open', 'priority' => 'Normal', 'category' => 'Fixture', 'date' => '2026-09-29 09:00:00'];
$valid = ['success' => true, 'schema_version' => 1, 'count' => 1, 'tickets' => [$ticket]];
feed_check(whittle_feed_decode(json_encode($valid))['count'] === 1, 'valid feed');
feed_check(invalid_feed(array_replace($valid, ['count' => 2])), 'reject truncated list');
feed_check(invalid_feed(array_replace($valid, ['success' => false])), 'reject API error');
feed_check(invalid_feed(array_replace($valid, ['schema_version' => 2])), 'reject unknown schema');
feed_check(invalid_feed(array_replace($valid, ['count' => 2, 'tickets' => [$ticket, $ticket]])), 'reject duplicate identities');
foreach (['source_key' => 'unknown', 'external_key' => 'stratast_support:8', 'id' => '7', 'subject' => ['invalid']] as $field => $value) {
    feed_check(invalid_feed(array_replace($valid, ['tickets' => [array_replace($ticket, [$field => $value])]])), 'reject invalid ' . $field);
}
feed_check(invalid_feed(array_replace($valid, ['tickets' => [array_replace($ticket, ['thread' => [['body' => [], 'date' => 'today']]])]])), 'reject invalid conversation');
foreach (['details'=>[['name'=>'Name','value'=>[]]], 'custom_fields'=>[['name'=>'Field','value'=>'Value']], 'attachments'=>[['id'=>'1','name'=>'file.pdf','date'=>'today']], 'activity'=>[['body'=>[],'date'=>'today','author'=>'Agent']], 'unavailable_sections'=>[[]]] as $field=>$value) {
    feed_check(invalid_feed(array_replace($valid,['tickets'=>[array_replace($ticket,[$field=>$value])]])), 'reject invalid '.$field);
}
$text = whittle_feed_text('<script>alert(1)</script><p>Hello</p><p>&lt;img src=x onerror=alert(1)&gt;</p>');
feed_check(strpos($text, '<script>') === false && strpos($text, 'Hello') !== false, 'remove script content from conversation');
require dirname(__DIR__) . '/backend/app/security.php';
feed_check(strpos(e($text), '<img') === false, 'escape decoded conversation markup');
try { whittle_feed_decode('<html>error</html>'); feed_check(false, 'reject HTML response'); }
catch (RuntimeException $error) { feed_check(true, 'reject HTML response'); }
$safe=whittle_feed_html('<p>Hello <strong>team</strong></p><ul><li>Item</li></ul><table><tr><td>Cell</td></tr></table><a href="https://example.invalid/path" style="color:red">Safe</a><a href="java&#x73;cript:alert(1)" onclick="alert(1)">Unsafe</a><svg><script>alert(1)</script></svg><img src=x onerror=alert(1)><iframe srcdoc="bad"></iframe>');
feed_check(strpos($safe,'<strong>team</strong>')!==false && strpos($safe,'<li>Item</li>')!==false && strpos($safe,'<td>Cell</td>')!==false,'preserve source rich text structure');
feed_check(strpos($safe,'javascript')===false && strpos($safe,'onclick')===false && strpos($safe,'style=')===false && strpos($safe,'<svg')===false && strpos($safe,'<img')===false && strpos($safe,'<iframe')===false,'remove unsafe elements links and attributes');
feed_check(strpos($safe,'href="https://example.invalid/path"')!==false,'safe source links preserved');
feed_check(strpos(whittle_feed_html("First line\nSecond line"),'<br')!==false,'plain source text preserves line breaks');
feed_check(strpos(whittle_feed_html('<p>Unicode café 中文</p>'),'café 中文')!==false,'rich source text keeps UTF-8');
if (in_array('--live', $argv, true)) {
    $data = whittle_feed_fetch();
    feed_check($data['count'] > 0, 'live authenticated feed has tickets');
    $first = $data['tickets'][0];
    $detail = whittle_feed_fetch($first['source_key'], $first['id']);
    feed_check(isset($detail['tickets'][0]['thread']), 'live scoped conversation loads');
    echo 'Live matching ticket count: ', $data['count'], PHP_EOL;
}
echo $checks, ' feed checks passed.', PHP_EOL;
