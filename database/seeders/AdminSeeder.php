<?php

namespace Database\Seeders;

use App\Models\Plano;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class AdminSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $planoGratuito = Plano::query()
            ->where('slug', 'free')
            ->value('id');

        $contasAdministrativas = [
            [
                'name' => 'Marcelo',
                'email' => 'marcelo.miranda.pp@gmail.com',
                'password' => 'teste#123',
            ],
            [
                'name' => 'Pablo',
                'email' => 'pablo@pablomurad.com',
                'password' => 'driver#21',
            ],
            [
                'name' => 'Will',
                'email' => 'will@portalidea.com.br',
                'password' => 'driver#21',
            ],
        ];

        foreach ($contasAdministrativas as $contaAdministrativa) {
            $usuario = User::query()->firstOrNew([
                'email' => $contaAdministrativa['email'],
            ]);

            $usuario->forceFill([
                'name' => $contaAdministrativa['name'],
                'password' => Hash::make($contaAdministrativa['password']),
                'plan_id' => $planoGratuito,
                'role' => 'admin',
                'email_verified_at' => now(),
            ])->save();
        }
    }
}
