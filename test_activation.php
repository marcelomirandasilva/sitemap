<?php
use Illuminate\Support\Facades\Http;

$response = Http::post('http://localhost/activate', [
    'email' => 'marcelo.miranda@mesquita.rj.gov.br',
    'token' => 'dummy',
    'password' => '12345678',
    'password_confirmation' => '12345678',
]);

echo $response->status() . "\n";
echo $response->body() . "\n";
