<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AnexoTicket extends Model
{
    protected $table = 'anexos_tickets';

    protected $fillable = [
        'ticket_id',
        'caminho_arquivo',
        'nome_original',
    ];

    public function ticket(): BelongsTo
    {
        return $this->belongsTo(Ticket::class, 'ticket_id');
    }
}
