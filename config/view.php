<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Caminhos das Views
    |--------------------------------------------------------------------------
    |
    | Define onde o Laravel deve procurar pelos arquivos Blade da aplicação.
    |
    */

    'paths' => [
        resource_path('views'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Caminho das Views Compiladas
    |--------------------------------------------------------------------------
    |
    | Define onde os templates Blade compilados serão armazenados. O uso de
    | storage_path evita falha quando o diretório ainda não existe.
    |
    */

    'compiled' => env(
        'VIEW_COMPILED_PATH',
        storage_path('framework/views')
    ),

];
