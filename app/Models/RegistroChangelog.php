<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class RegistroChangelog extends Model
{
    protected $table = 'registros_changelog';

    protected $fillable = [
        'versao',
        'data_lancamento',
        'ordem_exibicao',
        'publicado',
        'categoria_pt',
        'categoria_en',
        'titulo_pt',
        'titulo_en',
        'resumo_pt',
        'resumo_en',
        'itens_pt',
        'itens_en',
    ];

    protected $casts = [
        'data_lancamento' => 'date',
        'publicado' => 'boolean',
        'itens_pt' => 'array',
        'itens_en' => 'array',
    ];

    public function scopePublicados(Builder $query): Builder
    {
        return $query->where('publicado', true);
    }

    public function paraExibicao(string $locale): array
    {
        $localeNormalizado = str_starts_with(strtolower($locale), 'en') ? 'en' : 'pt';

        return [
            'versao' => $this->versao,
            'data' => optional($this->data_lancamento)->format('Y-m-d'),
            'categoria' => $this->textoLocalizado('categoria', $localeNormalizado),
            'titulo' => $this->textoLocalizado('titulo', $localeNormalizado),
            'resumo' => $this->textoLocalizado('resumo', $localeNormalizado),
            'itens' => $this->itensLocalizados($localeNormalizado),
        ];
    }

    protected function textoLocalizado(string $campoBase, string $locale): string
    {
        $campoPreferido = $campoBase . '_' . $locale;
        $campoAlternativo = $campoBase . '_' . ($locale === 'en' ? 'pt' : 'en');

        return (string) ($this->{$campoPreferido} ?: $this->{$campoAlternativo} ?: '');
    }

    protected function itensLocalizados(string $locale): array
    {
        $campoPreferido = 'itens_' . $locale;
        $campoAlternativo = 'itens_' . ($locale === 'en' ? 'pt' : 'en');

        $itens = $this->{$campoPreferido} ?: $this->{$campoAlternativo} ?: [];

        return collect($itens)
            ->map(fn ($item) => trim((string) $item))
            ->filter()
            ->values()
            ->all();
    }
}
