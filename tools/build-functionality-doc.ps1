param([string]$OutputName = 'Wheettle-Functionality-Draft-Revised.docx')
$ErrorActionPreference = 'Stop'
Add-Type -AssemblyName System.IO.Compression
Add-Type -AssemblyName System.IO.Compression.FileSystem
$projectRoot = Split-Path $PSScriptRoot -Parent
$source = Join-Path $projectRoot 'documentation/Functionality-Draft.md'
$target = Join-Path (Join-Path $projectRoot 'documentation') $OutputName
$body = [System.Text.StringBuilder]::new()
foreach ($line in [System.IO.File]::ReadAllLines($source)) {
    if (!$line.Trim()) { continue }
    $style = 'Normal'
    $value = $line
    if ($line.StartsWith('# ')) { $style = 'Title'; $value = $line.Substring(2) }
    elseif ($line.StartsWith('## ')) { $style = 'Heading1'; $value = $line.Substring(3) }
    elseif ($line.StartsWith('- ')) { $style = 'ListParagraph'; $value = [char]0x2022 + ' ' + $line.Substring(2) }
    $escaped = [System.Security.SecurityElement]::Escape($value)
    [void]$body.Append("<w:p><w:pPr><w:pStyle w:val=`"$style`"/></w:pPr><w:r><w:t xml:space=`"preserve`">$escaped</w:t></w:r></w:p>")
}
$entries = @{
    '[Content_Types].xml' = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?><Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types"><Default Extension="rels" ContentType="application/vnd.openxmlformats-package.relationships+xml"/><Default Extension="xml" ContentType="application/xml"/><Override PartName="/word/document.xml" ContentType="application/vnd.openxmlformats-officedocument.wordprocessingml.document.main+xml"/><Override PartName="/word/styles.xml" ContentType="application/vnd.openxmlformats-officedocument.wordprocessingml.styles+xml"/></Types>'
    '_rels/.rels' = '<?xml version="1.0" encoding="UTF-8"?><Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships"><Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/officeDocument" Target="word/document.xml"/></Relationships>'
    'word/_rels/document.xml.rels' = '<?xml version="1.0" encoding="UTF-8"?><Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships"><Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/styles" Target="styles.xml"/></Relationships>'
    'word/styles.xml' = '<?xml version="1.0" encoding="UTF-8"?><w:styles xmlns:w="http://schemas.openxmlformats.org/wordprocessingml/2006/main"><w:docDefaults><w:rPrDefault><w:rPr><w:rFonts w:ascii="Calibri" w:hAnsi="Calibri"/><w:sz w:val="22"/></w:rPr></w:rPrDefault><w:pPrDefault><w:pPr><w:spacing w:after="140" w:line="270" w:lineRule="auto"/></w:pPr></w:pPrDefault></w:docDefaults><w:style w:type="paragraph" w:default="1" w:styleId="Normal"><w:name w:val="Normal"/></w:style><w:style w:type="paragraph" w:styleId="Title"><w:name w:val="Title"/><w:basedOn w:val="Normal"/><w:pPr><w:keepNext/><w:spacing w:after="260"/></w:pPr><w:rPr><w:b/><w:color w:val="163D4A"/><w:sz w:val="42"/></w:rPr></w:style><w:style w:type="paragraph" w:styleId="Heading1"><w:name w:val="heading 1"/><w:basedOn w:val="Normal"/><w:pPr><w:keepNext/><w:spacing w:before="280" w:after="160"/><w:outlineLvl w:val="0"/></w:pPr><w:rPr><w:b/><w:color w:val="176B73"/><w:sz w:val="29"/></w:rPr></w:style><w:style w:type="paragraph" w:styleId="ListParagraph"><w:name w:val="List Paragraph"/><w:basedOn w:val="Normal"/><w:pPr><w:ind w:left="240" w:hanging="180"/></w:pPr></w:style></w:styles>'
    'word/document.xml' = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?><w:document xmlns:w="http://schemas.openxmlformats.org/wordprocessingml/2006/main"><w:body>' + $body.ToString() + '<w:sectPr><w:pgSz w:w="11906" w:h="16838"/><w:pgMar w:top="1134" w:right="1134" w:bottom="1134" w:left="1134"/></w:sectPr></w:body></w:document>'
}
$stream = [System.IO.File]::Open($target, [System.IO.FileMode]::Create)
$zip = [System.IO.Compression.ZipArchive]::new($stream, [System.IO.Compression.ZipArchiveMode]::Create)
try {
    foreach ($entry in $entries.GetEnumerator()) {
        $part = $zip.CreateEntry($entry.Key)
        $writer = [System.IO.StreamWriter]::new($part.Open(), [System.Text.UTF8Encoding]::new($false))
        try { $writer.Write($entry.Value) } finally { $writer.Dispose() }
    }
} finally { $zip.Dispose(); $stream.Dispose() }
$check = [System.IO.Compression.ZipFile]::OpenRead($target)
try {
    foreach ($part in $check.Entries) {
        $reader = [System.IO.StreamReader]::new($part.Open())
        try { [xml]$xml = $reader.ReadToEnd() } finally { $reader.Dispose() }
    }
    Write-Output "PASS: DOCX ZIP and all $($check.Entries.Count) XML parts validated: $target"
} finally { $check.Dispose() }
