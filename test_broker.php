<?php
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\User;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rules;

$email = 'marcelo.miranda@mesquita.rj.gov.br';
$user = User::where('email', $email)->first();

if (!$user) {
    die("User not found\n");
}

$token = Password::broker()->createToken($user);

$data = [
    'email' => $email,
    'token' => $token,
    'password' => '12345678',
    'password_confirmation' => '12345678',
];

$validator = Validator::make($data, [
    'token' => 'required',
    'email' => 'required|email',
    'password' => ['required', 'confirmed', Rules\Password::defaults()],
]);

if ($validator->fails()) {
    echo "Validation Failed:\n";
    print_r($validator->errors()->messages());
    exit;
}

echo "Validation Passed. Calling Password::reset...\n";

$status = Password::reset(
    $data,
    function ($user) use ($data) {
        echo "Inside reset closure... Password updated!\n";
    }
);

echo "Final Status: " . $status . " (" . trans($status) . ")\n";
