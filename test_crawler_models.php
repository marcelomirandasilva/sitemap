<?php

require __DIR__ . '/vendor/autoload.php';

$app = require __DIR__ . '/bootstrap/app.php';

$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\Project;
use App\Models\Page;
use App\Models\Link;
use App\Models\User;

// Limpar dados de teste anteriores apenas para esse test project
Project::where('name', 'Crawler Test Project')->delete();

// Garantir que temos um usuario para associar
$user = User::first();
if (!$user) {
    die("ERRO: Nenhum usuário encontrado no banco de dados para vincular o projeto.\n");
}

// 1. Criar Projeto
try {
    $project = Project::create([
        'user_id' => $user->id,
        'name' => 'Crawler Test Project',
        'url' => 'https://example.com',
        'frequency' => 'daily', // Campo obrigatório
        'max_depth' => 5,
        'check_images' => true,
    ]);

    echo "Projeto criado: {$project->name} (Check Images: " . ($project->check_images ? 'Sim' : 'Não') . ")\n";

    // 2. Adicionar Página
    $page = $project->pages()->create([
        'url' => 'https://example.com',
        'path_hash' => md5('https://example.com'),
        'status_code' => 200,
        'title' => 'Home Page',
    ]);

    echo "Página criada: {$page->title} (ID: {$page->id})\n";

    // 3. Adicionar Link
    $link = $page->links()->create([
        'project_id' => $project->id,
        'target_url' => 'https://example.com/about',
        'is_external' => false,
        'anchor_text' => 'About Us',
    ]);

    echo "Link criado: {$link->anchor_text} -> {$link->target_url}\n";

    // 4. Testar Relacionamentos Inversos e Through
    $retrievedProject = $page->project;
    echo "Página pertence ao projeto: {$retrievedProject->name}\n";

    $projectLinksCount = $project->links()->count();
    echo "Total de links no projeto (via hasMany): {$projectLinksCount}\n";

    if ($projectLinksCount === 1 && $retrievedProject->id === $project->id) {
        echo "VERIFICAÇÃO BEM SUCEDIDA! ✅\n";
    } else {
        echo "FALHA NA VERIFICAÇÃO ❌\n";
    }

} catch (\Exception $e) {
    echo "ERRO DE EXECUÇÃO: " . $e->getMessage() . "\n";
    echo $e->getTraceAsString();
}
