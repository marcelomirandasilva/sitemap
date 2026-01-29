<?php

$filePath = 'd:/www/api-sitemap/sitemaps/projects/7/sitemap.xml';

if (!file_exists($filePath)) {
    die("Arquivo não encontrado: $filePath\n");
}

$reader = new \XMLReader();
if (!$reader->open($filePath)) {
    die("Falha ao abrir XML\n");
}

echo "Lendo arquivo com lógica atual...\n";

$count = 0;
while ($reader->read() && $count < 5) {
    if ($reader->nodeType === \XMLReader::ELEMENT && $reader->name === 'url') {
        $node = new \SimpleXMLElement($reader->readOuterXML());

        // LOGICA IDÊNTICA AO SERVICE ATUAL
        $loc = (string) $node->loc;
        $lastmod = (string) $node->lastmod;

        if (empty($loc)) {
            echo "  [INFO] Acesso direto falhou. Tentando namespace...\n";
            $ns = $node->getNamespaces(true);

            // Debug namespaces found
            print_r($ns);

            $nsUrl = isset($ns['']) ? $ns[''] : "http://www.sitemaps.org/schemas/sitemap/0.9";

            echo "  [INFO] Usando namespace URL: $nsUrl\n";

            $child = $node->children($nsUrl);

            if ($child->count() > 0) {
                $loc = (string) $child->loc;
                $lastmod = (string) $child->lastmod;
            } else {
                echo "  [FAIL] children() retornou vazio.\n";
            }
        }

        echo "URL: $loc\n";
        echo "LastMod: $lastmod\n";
        echo "-----------------\n";
        $count++;
    }
}

$reader->close();
