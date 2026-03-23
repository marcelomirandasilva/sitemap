<?php

namespace App\Filament\Widgets;

use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use App\Models\User;
use App\Models\Projeto;
use Illuminate\Support\Facades\DB;

class StatsOverviewWidget extends BaseWidget
{
    protected function getStats(): array
    {
        $usersCount = User::count();
        $projectsCount = Projeto::where('status', 'active')->count();
        $jobsCount = DB::table('sitemap_jobs')
            ->whereIn('status', ['queued', 'running'])
            ->count();

        $newUsersChart = User::selectRaw('DATE(created_at) as date, COUNT(*) as count')
            ->where('created_at', '>=', now()->subDays(7))
            ->groupBy('date')
            ->orderBy('date')
            ->pluck('count')
            ->toArray();

        // Evitar erro se o array estiver vazio para o chart
        if (empty($newUsersChart)) {
            $newUsersChart = [0, 0, 0];
        }

        return [
            Stat::make('Total de Usuários', $usersCount)
                ->description('Usuários registrados')
                ->descriptionIcon('heroicon-m-users')
                ->chart($newUsersChart)
                ->color('success'),
            Stat::make('Projetos Ativos', $projectsCount)
                ->description('Projetos ativos na plataforma')
                ->descriptionIcon('heroicon-m-briefcase')
                ->color('primary'),
            Stat::make('Jobs na Fila/Rodando', $jobsCount)
                ->description('Visão do crawler local')
                ->descriptionIcon('heroicon-m-cog')
                ->color($jobsCount > 0 ? 'warning' : 'success'),
        ];
    }
}
