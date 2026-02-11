<?php

$filePath = 'd:/www/api-sitemap/sitemaps/projects/7/sitemap.xml';

if (!file_exists($filePath)) {
    die("Arquivo não encontrado\n");
}

$reader = new \XMLReader();
if (!$reader->open($filePath)) {
    die("Falha ao abrir XML\n");
}

echo "Lendo arquivo COMPLETO...\n";

$count = 0;
$success = 0;
$errors = 0;
$skipped = 0;

while ($reader->read()) {
    if ($reader->nodeType === \XMLReader::ELEMENT && $reader->name === 'url') {
        $count++;
        try {
            $outerXml = $reader->readOuterXML();
            // Regex fix
            $outerXml = preg_replace('/&(?!amp;|lt;|gt;|quot;|apos;)/', '&amp;', $outerXml);

            $node = new \SimpleXMLElement($outerXml, LIBXML_NOCDATA);

            // Logic match
            $loc = (string) $node->loc;
            if (empty($loc)) {
                $ns = $node->getNamespaces(true);
                $nsUrl = isset($ns['']) ? $ns[''] : "http://www.sitemaps.org/schemas/sitemap/0.9";
                $child = $node->children($nsUrl);
                $loc = (string) $child->loc;
            }

            if (!empty($loc)) {
                $success++;
            } else {
                $skipped++;
            }

        } catch (\Throwable $e) {
            $errors++;
            echo "ERROR: " . $e->getMessage() . "\n";
        }
    }
}

echo "-----------------\n";
echo "Total <url> tags: $count\n";
echo "Success parses: $success\n";
echo "Errors: $errors\n";
echo "Skipped: $skipped\n";

$reader->close();
