<?php

namespace App\Support;

use Illuminate\Validation\ValidationException;

class ValidadorUrlExterna
{
    public function validarOuFalhar(string $url, string $campo = 'url'): void
    {
        $mensagem = $this->mensagemErro($url);

        if ($mensagem !== null) {
            throw ValidationException::withMessages([
                $campo => $mensagem,
            ]);
        }
    }

    public function mensagemErro(string $url): ?string
    {
        $partes = parse_url($url);

        if (!is_array($partes)) {
            return 'A URL informada e invalida.';
        }

        $esquema = strtolower((string) ($partes['scheme'] ?? ''));
        $host = strtolower((string) ($partes['host'] ?? ''));

        if (!in_array($esquema, ['http', 'https'], true)) {
            return 'Somente URLs HTTP ou HTTPS sao permitidas.';
        }

        if ($host === '') {
            return 'A URL precisa informar um host valido.';
        }

        if (isset($partes['user']) || isset($partes['pass'])) {
            return 'URLs com credenciais embutidas nao sao permitidas.';
        }

        if ($this->hostInternoReservado($host)) {
            return 'Hosts locais, internos ou reservados nao sao permitidos.';
        }

        if (filter_var($host, FILTER_VALIDATE_IP)) {
            return $this->ipPublico($host)
                ? null
                : 'Enderecos IP privados, locais ou reservados nao sao permitidos.';
        }

        $registros = @dns_get_record($host, DNS_A | DNS_AAAA);

        if ($registros === false || $registros === [] || $registros === null) {
            return 'Nao foi possivel resolver o host informado.';
        }

        foreach ($registros as $registro) {
            $ip = $registro['ip'] ?? $registro['ipv6'] ?? null;

            if (!$ip) {
                continue;
            }

            if (!$this->ipPublico($ip)) {
                return 'O host informado resolve para um endereco privado, local ou reservado.';
            }
        }

        return null;
    }

    protected function hostInternoReservado(string $host): bool
    {
        if (in_array($host, ['localhost', 'host.docker.internal'], true)) {
            return true;
        }

        foreach (['.local', '.localhost', '.internal', '.test', '.home', '.lan'] as $sufixo) {
            if (str_ends_with($host, $sufixo)) {
                return true;
            }
        }

        return false;
    }

    protected function ipPublico(string $ip): bool
    {
        return filter_var(
            $ip,
            FILTER_VALIDATE_IP,
            FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE
        ) !== false;
    }
}
