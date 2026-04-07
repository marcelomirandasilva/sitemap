<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use RuntimeException;

class BingWebmasterService
{
    protected string $endpoint = 'https://www.bing.com/webmasterapi/api.svc/soap';
    protected string $bodyNamespace = 'http://tempuri.org/';
    protected string $actionNamespace = 'http://schemas.datacontract.org/2004/07/Microsoft.Bing.Webmaster.Api/IWebmasterApi';

    public function listSites(string $apiKey): array
    {
        $response = $this->sendSoapRequest($apiKey, 'GetUserSites');
        $xml = $this->parseXml($response);

        return collect($xml->xpath('//*[local-name()="GetUserSitesResult"]/*[local-name()="string"]') ?: [])
            ->map(fn ($node) => trim((string) $node))
            ->filter()
            ->values()
            ->all();
    }

    public function submitSitemap(string $apiKey, string $siteUrl, string $sitemapUrl): array
    {
        $response = $this->sendSoapRequest($apiKey, 'SubmitFeed', [
            'siteUrl' => $siteUrl,
            'feedUrl' => $sitemapUrl,
        ]);

        $this->parseXml($response);

        return [
            'site_identifier' => $siteUrl,
            'sitemap_url' => $sitemapUrl,
            'submitted' => true,
        ];
    }

    protected function sendSoapRequest(string $apiKey, string $operation, array $parameters = []): string
    {
        $xml = $this->buildSoapEnvelope($operation, $parameters);

        $response = Http::withHeaders([
            'Content-Type' => 'text/xml; charset=utf-8',
            'SOAPAction' => '"' . $this->actionNamespace . '/' . $operation . '"',
        ])
            ->timeout(20)
            ->withBody($xml, 'text/xml; charset=utf-8')
            ->post($this->endpoint . '?apikey=' . urlencode($apiKey));

        if ($response->failed()) {
            throw new RuntimeException('Bing Webmaster Tools rejeitou a requisicao (' . $response->status() . ').');
        }

        return $response->body();
    }

    protected function buildSoapEnvelope(string $operation, array $parameters = []): string
    {
        $fields = collect($parameters)->map(function ($value, $key) {
            $escaped = htmlspecialchars((string) $value, ENT_XML1 | ENT_QUOTES, 'UTF-8');

            return "<{$key}>{$escaped}</{$key}>";
        })->implode('');

        return <<<XML
<?xml version="1.0" encoding="utf-8"?>
<s:Envelope xmlns:s="http://schemas.xmlsoap.org/soap/envelope/">
  <s:Body>
    <{$operation} xmlns="{$this->bodyNamespace}">{$fields}</{$operation}>
  </s:Body>
</s:Envelope>
XML;
    }

    protected function parseXml(string $xml): \SimpleXMLElement
    {
        libxml_use_internal_errors(true);
        $parsed = simplexml_load_string($xml);

        if (!$parsed) {
            throw new RuntimeException('Nao foi possivel interpretar a resposta do Bing Webmaster Tools.');
        }

        $fault = $parsed->xpath('//*[local-name()="Fault"]/*[local-name()="faultstring"]');
        if (!empty($fault)) {
            throw new RuntimeException(trim((string) $fault[0]));
        }

        return $parsed;
    }
}
