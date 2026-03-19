<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Ticket extends Model
{
    protected $table = 'tickets';

    protected $fillable = [
        'user_id',
        'projeto_id',
        'titulo',
        'mensagem',
        'status',
    ];

    /**
     * Status disponíveis para um ticket
     */
    const STATUS_ABERTO = 'aberto';
    const STATUS_EM_ANALISE = 'em_analise';
    const STATUS_EM_ATENDIMENTO = 'em_atendimento';
    const STATUS_RESPONDIDO = 'respondido';
    const STATUS_AGUARDANDO_USUARIO = 'aguardando_usuario';
    const STATUS_FECHADO = 'fechado';

    /**
     * Retorna todos os status possíveis.
     */
    public static function listarStatus(): array
    {
        return [
            self::STATUS_ABERTO,
            self::STATUS_EM_ANALISE,
            self::STATUS_EM_ATENDIMENTO,
            self::STATUS_RESPONDIDO,
            self::STATUS_AGUARDANDO_USUARIO,
            self::STATUS_FECHADO,
        ];
    }

    // --- Relacionamentos ---

    public function usuario(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function projeto(): BelongsTo
    {
        return $this->belongsTo(Projeto::class, 'projeto_id');
    }

    public function respostas(): HasMany
    {
        return $this->hasMany(RespostaTicket::class, 'ticket_id')->orderBy('created_at');
    }

    public function anexos(): HasMany
    {
        return $this->hasMany(AnexoTicket::class, 'ticket_id');
    }
}
