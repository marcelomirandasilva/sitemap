<?php

namespace App\Policies;

use App\Models\ChaveApi;
use App\Models\User;

class ChaveApiPolicy
{
    /**
     * Garante que o usuário só pode gerenciar as próprias chaves.
     */
    public function manage(User $user, ChaveApi $chaveApi): bool
    {
        return $user->id === $chaveApi->user_id;
    }
}
