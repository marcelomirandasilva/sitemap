<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;

class SitemapDataReaderService
{
    public function getPaginatedUrls(string $filePath, int $page = 1, int $perPage = 50, ?string $search = null): array
    {
        if (!file_exists($filePath)) {
            return ['data' => [], 'total' => 0];
        }

        $extensaoOriginal = strtolower(pathinfo($filePath, PATHINFO_EXTENSION));

        if (in_array($extensaoOriginal, ['gz', 'zip'], true)) {
            $conteudo = $this->lerConteudoCompactado($filePath);

            if ($conteudo === null) {
                return ['data' => [], 'total' => 0];
            }

            return $this->getPaginatedUrlsFromContent($conteudo, basename($filePath), $page, $perPage, $search);
        }

        $extensao = $this->resolverExtensaoConteudo($filePath);

        if ($extensao === 'xml') {
            return $this->readXmlPaginated($filePath, $page, $perPage, $search);
        }

        return $this->readTxtPaginated($filePath, $page, $perPage, $search);
    }

    public function getPaginatedUrlsFromContent(string $content, string $filename, int $page = 1, int $perPage = 50, ?string $search = null): array
    {
        $extensao = $this->resolverExtensaoConteudo($filename);

        if ($extensao === 'xml') {
            return $this->readXmlPaginatedFromContent($content, $page, $perPage, $search);
        }

        return $this->readTxtPaginatedFromContent($content, $page, $perPage, $search);
    }

    protected function resolverExtensaoConteudo(string $filename): string
    {
        $nome = strtolower(basename($filename));

        if (str_ends_with($nome, '.xml.gz') || str_ends_with($nome, '.xml.zip')) {
            return 'xml';
        }

        if (str_ends_with($nome, '.txt.gz') || str_ends_with($nome, '.txt.zip')) {
            return 'txt';
        }

        return strtolower(pathinfo($nome, PATHINFO_EXTENSION));
    }

    protected function lerConteudoCompactado(string $filePath): ?string
    {
        $extensao = strtolower(pathinfo($filePath, PATHINFO_EXTENSION));

        if ($extensao === 'gz') {
            $conteudo = @file_get_contents($filePath);

            if ($conteudo === false) {
                return null;
            }

            $descompactado = function_exists('gzdecode') ? @gzdecode($conteudo) : false;

            return $descompactado === false ? null : $descompactado;
        }

        if ($extensao === 'zip' && class_exists(\ZipArchive::class)) {
            $zip = new \ZipArchive();

            if ($zip->open($filePath) !== true) {
                return null;
            }

            $conteudo = $zip->numFiles > 0 ? $zip->getFromIndex(0) : false;
            $zip->close();

            return $conteudo === false ? null : $conteudo;
        }

        return null;
    }

    protected function readTxtPaginated(string $filePath, int $page, int $perPage, ?string $search): array
    {
        $content = @file_get_contents($filePath);

        if ($content === false) {
            return ['data' => [], 'total' => 0];
        }

        return $this->readTxtPaginatedFromContent($content, $page, $perPage, $search);
    }

    protected function readTxtPaginatedFromContent(string $content, int $page, int $perPage, ?string $search): array
    {
        $lines = preg_split("/\r\n|\n|\r/", $content) ?: [];
        $results = [];
        $totalMatches = 0;
        $offset = ($page - 1) * $perPage;
        $collected = 0;

        foreach ($lines as $line) {
            $line = trim((string) $line);

            if ($line === '') {
                continue;
            }

            if ($search && stripos($line, $search) === false) {
                continue;
            }

            if ($totalMatches >= $offset && $collected < $perPage) {
                $results[] = [
                    'url' => $line,
                    'lastMod' => null,
                    'priority' => '-',
                    'changeFreq' => '-',
                ];
                $collected++;
            }

            $totalMatches++;
        }

        return [
            'data' => $results,
            'total' => $totalMatches,
        ];
    }

    protected function readXmlPaginated(string $filePath, int $page, int $perPage, ?string $search): array
    {
        if (filesize($filePath) < 5 * 1024 * 1024) {
            $content = @file_get_contents($filePath);

            if ($content === false) {
                return ['data' => [], 'total' => 0];
            }

            return $this->readXmlPaginatedFromContent($content, $page, $perPage, $search);
        }

        return $this->readXmlStreamed($filePath, $page, $perPage, $search);
    }

    protected function readXmlPaginatedFromContent(string $content, int $page, int $perPage, ?string $search): array
    {
        $content = preg_replace('/&(?!amp;|lt;|gt;|quot;|apos;)/', '&amp;', $content);

        try {
            $xml = new \SimpleXMLElement($content, LIBXML_NOCDATA);
            $ns = $xml->getNamespaces(true);
            $nsUrl = $ns[''] ?? 'http://www.sitemaps.org/schemas/sitemap/0.9';

            if ($nsUrl) {
                $xml->registerXPathNamespace('s', $nsUrl);
                $items = $xml->xpath('//s:url');
            } else {
                $items = $xml->xpath('//url');
            }

            if (empty($items)) {
                if ($nsUrl) {
                    $items = $xml->children($nsUrl)->url;
                }

                if (empty($items) || count($items) === 0) {
                    $items = $xml->children()->url;
                }
            }

            if (empty($items) && $xml->count() > 0 && $xml->getName() === 'urlset') {
                $items = $xml;
            }

            $allParams = [];

            foreach ($items as $node) {
                if ($node->getName() !== 'url') {
                    continue;
                }

                $loc = (string) $node->loc;
                $lastmod = (string) $node->lastmod;
                $priority = (string) $node->priority;
                $changefreq = (string) $node->changefreq;

                if ($loc === '' && $nsUrl) {
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
                    'lastMod' => $lastmod !== '' ? $lastmod : null,
                    'priority' => $priority !== '' ? $priority : null,
                    'changeFreq' => $changefreq !== '' ? $changefreq : null,
                ];
            }

            return [
                'data' => array_slice($allParams, ($page - 1) * $perPage, $perPage),
                'total' => count($allParams),
            ];
        } catch (\Throwable $e) {
            Log::error('Erro ao ler XML sanitizado: ' . $e->getMessage());

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
                    $outerXml = preg_replace('/&(?!amp;|lt;|gt;|quot;|apos;)/', '&amp;', $outerXml);

                    $node = new \SimpleXMLElement($outerXml, LIBXML_NOCDATA);
                    $loc = (string) $node->loc;
                    $lastmod = (string) $node->lastmod;
                    $priority = (string) $node->priority;
                    $changefreq = (string) $node->changefreq;

                    if ($loc === '') {
                        $ns = $node->getNamespaces(true);
                        $nsUrl = $ns[''] ?? 'http://www.sitemaps.org/schemas/sitemap/0.9';
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
                            'url' => $loc,
                            'lastMod' => $lastmod !== '' ? $lastmod : null,
                            'priority' => $priority !== '' ? $priority : null,
                            'changeFreq' => $changefreq !== '' ? $changefreq : null,
                        ];
                        $collected++;
                    }

                    $totalMatches++;
                } catch (\Throwable) {
                    continue;
                }
            }
        }

        $reader->close();

        return [
            'data' => $results,
            'total' => $totalMatches,
        ];
    }
}
