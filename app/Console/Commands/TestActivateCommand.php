<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use App\Models\User;
use Illuminate\Support\Facades\Password;

class TestActivateCommand extends Command
{
    protected $signature = 'test:activate';
    protected $description = 'Test the activation store method';

    public function handle()
    {
        $user = User::where('email', 'marcelo.miranda@mesquita.rj.gov.br')->first();
        if (!$user) {
            $this->error('User not found');
            return;
        }

        $token = Password::broker()->createToken($user);

        $request = \Illuminate\Http\Request::create('/activate', 'POST', [
            'email' => $user->email,
            'token' => $token,
            'password' => '12345678',
            'password_confirmation' => '12345678',
        ]);

        $response = app()->handle($request);

        $this->info('Status: ' . $response->getStatusCode());

        if ($response->isRedirect()) {
            $this->info('Redirected to: ' . $response->headers->get('Location'));
            $session = $request->session();
            if ($session && $session->has('errors')) {
                $this->error('Validation errors:');
                $this->line(json_encode($session->get('errors')->getMessages(), JSON_PRETTY_PRINT));
            } elseif ($session && $session->has('success')) {
                $this->info('Success message: ' . $session->get('success'));
            }
        } else {
            $this->line($response->getContent());
        }
    }
}
