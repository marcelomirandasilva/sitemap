<?php

namespace App\Services;

use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;

class SitemapDataReaderService
{
    /**
     * Lê um artefato (TXT ou XML) e retorna uma fatia paginada das URLs.
     * 
     * @param string $filePath Caminho absoluto ou relativo para o arquivo.
     * @param int $page Página atual (1-based).
     * @param int $perPage Itens por página.
     * @param string|null $search Termo de busca opcional.
     * @return array ['data' => [], 'total' => 0]
     */
    public function getPaginatedUrls(string $filePath, int $page = 1, int $perPage = 50, ?string $search = null): array
    {
        if (!file_exists($filePath)) {
            return ['data' => [], 'total' => 0];
        }

        $extension = pathinfo($filePath, PATHINFO_EXTENSION);

        if ($extension === 'xml') {
            return $this->readXmlPaginated($filePath, $page, $perPage, $search);
        }

        return $this->readTxtPaginated($filePath, $page, $perPage, $search);
    }

    protected function readTxtPaginated(string $filePath, int $page, int $perPage, ?string $search): array
    {
        $handle = fopen($filePath, "r");
        if (!$handle)
            return ['data' => [], 'total' => 0];

        $results = [];
        $totalMatches = 0;
        $offset = ($page - 1) * $perPage;

        $collected = 0;

        while (($line = fgets($handle)) !== false) {
            $line = trim($line);
            if (empty($line))
                continue;

            // Filtro de Busca
            if ($search && stripos($line, $search) === false) {
                continue;
            }

            // Paginação
            if ($totalMatches >= $offset && $collected < $perPage) {
                $results[] = [
                    'url' => $line,
                    'lastMod' => null, // TXT geralmente não tem data
                    'priority' => '-',
                    'changeFreq' => '-'
                ];
                $collected++;
            }

            $totalMatches++;
        }

        fclose($handle);

        return [
            'data' => $results,
            'total' => $totalMatches
        ];
    }

    protected function readXmlPaginated(string $filePath, int $page, int $perPage, ?string $search): array
    {
        // Se o arquivo for pequeno (< 5MB), lemos tudo na memória para corrigir erros de sintaxe (ex: & não escapado)
        if (filesize($filePath) < 5 * 1024 * 1024) {
            return $this->readXmlFullSanitized($filePath, $page, $perPage, $search);
        }

        return $this->readXmlStreamed($filePath, $page, $perPage, $search);
    }

    protected function readXmlFullSanitized(string $filePath, int $page, int $perPage, ?string $search): array
    {
        $content = file_get_contents($filePath);

        // CORREÇÃO: Substitui & solto por &amp; em todo o arquivo
        $content = preg_replace('/&(?!amp;|lt;|gt;|quot;|apos;)/', '&amp;', $content);

        try {
            $xml = new \SimpleXMLElement($content, LIBXML_NOCDATA);

            // Lidar com Namespaces
            $ns = $xml->getNamespaces(true);
            $nsUrl = isset($ns['']) ? $ns[''] : "http://www.sitemaps.org/schemas/sitemap/0.9";

            // Se tiver namespace, registra para usar xpath ou children
            if ($nsUrl) {
                $xml->registerXPathNamespace('s', $nsUrl);
                $items = $xml->xpath('//s:url');
            } else {
                $items = $xml->xpath('//url');
            }

            // Se xpath falhar (array vazio), tenta children manual
            if (empty($items)) {
                // Tenta compatibilidade com XMLs malformados ou mistos
                if ($nsUrl) {
                    $items = $xml->children($nsUrl)->url;
                }
                if (empty($items) || count($items) == 0) {
                    $items = $xml->children()->url;
                }
            }

            // Se ainda assim vazio, pode ser que children() retorne SimpleXMLElement iterável
            if (empty($items) && $xml->count() > 0 && $xml->getName() == 'urlset') {
                // Fallback final: iterar em tudo
                $items = $xml;
            }

            $allParams = [];

            foreach ($items as $node) {
                if ($node->getName() !== 'url')
                    continue;

                $loc = (string) $node->loc;
                $lastmod = (string) $node->lastmod;
                $priority = (string) $node->priority;
                $changefreq = (string) $node->changefreq;

                // Namespace fallback se vazio
                if (empty($loc) && $nsUrl) {
                    $child = $node->children($nsUrl);
                    $loc = (string) $child->loc;
                    $lastmod = (string) $child->lastmod;
                    $priority = (string) $child->priority;
                    $changefreq = (string) $child->changefreq;
                }

                if ($search && stripos($loc, $search) === false) {
                    continue;
                }

                $allParams[] = [
                    'url' => $loc,
                    'lastMod' => !empty($lastmod) ? $lastmod : null,
                    'priority' => !empty($priority) ? $priority : null,
                    'changeFreq' => !empty($changefreq) ? $changefreq : null
                ];
            }

            // Paginação em Array (slice)
            $total = count($allParams);
            $slice = array_slice($allParams, ($page - 1) * $perPage, $perPage);

            return [
                'data' => $slice,
                'total' => $total
            ];

        } catch (\Throwable $e) {
            Log::error("Erro ao ler XML sanitizado: " . $e->getMessage());
            return ['data' => [], 'total' => 0];
        }
    }

    protected function readXmlStreamed(string $filePath, int $page, int $perPage, ?string $search): array
    {
        $reader = new \XMLReader();
        if (!$reader->open($filePath)) {
            return ['data' => [], 'total' => 0];
        }

        $results = [];
        $totalMatches = 0;
        $offset = ($page - 1) * $perPage;
        $collected = 0;

        while ($reader->read()) {
            if ($reader->nodeType === \XMLReader::ELEMENT && $reader->name === 'url') {
                try {
                    $outerXml = $reader->readOuterXML();

                    // Regex fix em cada nó (menos eficiente, mas necessário para reader stream)
                    $outerXml = preg_replace('/&(?!amp;|lt;|gt;|quot;|apos;)/', '&amp;', $outerXml);

                    $node = new \SimpleXMLElement($outerXml, LIBXML_NOCDATA);

                    $loc = (string) $node->loc;
                    $lastmod = (string) $node->lastmod;
                    $priority = (string) $node->priority;
                    $changefreq = (string) $node->changefreq;

                    if (empty($loc)) {
                        $ns = $node->getNamespaces(true);
                        $nsUrl = isset($ns['']) ? $ns[''] : "http://www.sitemaps.org/schemas/sitemap/0.9";

                        $child = $node->children($nsUrl);
                        if ($child->count() > 0) {
                            $loc = (string) $child->loc;
                            $lastmod = (string) $child->lastmod;
                            $priority = (string) $child->priority;
                            $changefreq = (string) $child->changefreq;
                        }
                    }

                    if ($search && stripos($loc, $search) === false) {
                        continue;
                    }

                    if ($totalMatches >= $offset && $collected < $perPage) {
                        $results[] = [
                            'url' => (string) $loc,
                            'lastMod' => !empty($lastmod) ? (string) $lastmod : null,
                            'priority' => !empty($priority) ? (string) $priority : null,
                            'changeFreq' => !empty($changefreq) ? (string) $changefreq : null
                        ];
                        $collected++;
                    }

                    $totalMatches++;

                } catch (\Throwable $e) {
                    continue;
                }
            }
        }

        $reader->close();

        return [
            'data' => $results,
            'total' => $totalMatches
        ];
    }
}
