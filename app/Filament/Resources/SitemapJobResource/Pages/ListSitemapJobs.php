<?php

namespace App\Filament\Resources\SitemapJobResource\Pages;

use App\Filament\Resources\SitemapJobResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListSitemapJobs extends ListRecords
{
    protected static string $resource = SitemapJobResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
