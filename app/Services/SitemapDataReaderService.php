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

        // Se tem busca, precisamos ler tudo para contar (não tem jeito fácil sem indexar)
        // Se não tem busca, poderíamos pular linhas, mas strings têm tamanhos variáveis.
        // Vamos iterar streamado para economizar memória.

        $currentIndex = 0;
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
        // XMLReader é mais eficiente para arquivos grandes que SimpleXML
        $reader = new \XMLReader();
        if (!$reader->open($filePath)) {
            return ['data' => [], 'total' => 0];
        }

        $results = [];
        $totalMatches = 0;
        $offset = ($page - 1) * $perPage;
        $collected = 0;

        // Loop nos elementos <url>
        while ($reader->read()) {
            if ($reader->nodeType === \XMLReader::ELEMENT && $reader->name === 'url') {
                try {
                    $outerXml = $reader->readOuterXML();

                    // CORREÇÃO CRÍTICA: Substitui & solto por &amp; para evitar erro "EntityRef: expecting ;"
                    $outerXml = preg_replace('/&(?!amp;|lt;|gt;|quot;|apos;)/', '&amp;', $outerXml);

                    // LIBXML_NOCDATA ajuda a ignorar CDATA se existir
                    $node = new \SimpleXMLElement($outerXml, LIBXML_NOCDATA);

                    // Tenta ler direto (sem namespace explícito)
                    $loc = (string) $node->loc;
                    $lastmod = (string) $node->lastmod;
                    $priority = (string) $node->priority;
                    $changefreq = (string) $node->changefreq;

                    // Se loc estiver vazio, tenta namespace
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

                    // Filtro de Busca
                    if ($search && stripos($loc, $search) === false) {
                        continue;
                    }

                    // Paginação
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
                    // Ignora nó inválido para não quebrar a tabela inteira
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
