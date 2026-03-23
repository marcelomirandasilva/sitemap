<?php

namespace App\Filament\Resources;

use App\Filament\Resources\SitemapJobResource\Pages;
use App\Models\TarefaSitemap;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Infolists\Infolist;
use Filament\Infolists\Components\TextEntry;
use Filament\Infolists\Components\Section;

class SitemapJobResource extends Resource
{
    protected static ?string $model = TarefaSitemap::class;

    protected static ?string $navigationIcon = 'heroicon-o-cpu-chip';

    protected static ?string $navigationLabel = 'Jobs do Crawler';
    protected static ?string $pluralLabel = 'Jobs do Crawler';
    protected static ?string $modelLabel = 'Job do Crawler';

    public static function canCreate(): bool
    {
        return false;
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('projeto.name')
                    ->label('Projeto')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('projeto.url')
                    ->label('URL')
                    ->searchable()
                    ->url(fn(TarefaSitemap $record) => $record->projeto?->url)
                    ->openUrlInNewTab(),
                Tables\Columns\TextColumn::make('status')
                    ->badge()
                    ->color(fn(string $state): string => match ($state) {
                        'queued' => 'gray',
                        'running' => 'warning',
                        'completed' => 'success',
                        'failed' => 'danger',
                        default => 'secondary',
                    }),
                Tables\Columns\TextColumn::make('pages_count')
                    ->label('Páginas Processadas')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('started_at')
                    ->label('Tempo Decorrido')
                    ->getStateUsing(function (TarefaSitemap $record) {
                        if (!$record->started_at)
                            return '-';
                        $end = $record->completed_at ?? now();
                        return $record->started_at->diffForHumans($end, true);
                    }),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Criado em')
                    ->dateTime('d/m/Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                //
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\Action::make('cancel')
                    ->label('Cancelar Job')
                    ->icon('heroicon-o-x-circle')
                    ->color('danger')
                    ->requiresConfirmation()
                    ->visible(fn(TarefaSitemap $record) => in_array($record->status, ['queued', 'running']))
                    ->action(function (TarefaSitemap $record) {
                        $record->update([
                            'status' => 'failed',
                            'message' => 'Cancelado remotamente pelo Administrador.'
                        ]);
                        \Filament\Notifications\Notification::make()
                            ->title('Job Cancelado com sucesso')
                            ->success()
                            ->send();
                    }),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([
                Section::make('Detalhes do Job')
                    ->schema([
                        TextEntry::make('external_job_id')->label('ID Externo (Processo)'),
                        TextEntry::make('status')
                            ->badge()
                            ->color(fn(string $state): string => match ($state) {
                                'queued' => 'gray',
                                'running' => 'warning',
                                'completed' => 'success',
                                'failed' => 'danger',
                                default => 'secondary',
                            }),
                        TextEntry::make('message')->label('Mensagem de Erro/Status'),
                        TextEntry::make('progress')->label('Progresso (%)')->suffix('%'),
                        TextEntry::make('pages_count')->label('Páginas Encontradas'),
                        TextEntry::make('images_count')->label('Imagens Analisadas'),
                        TextEntry::make('started_at')->label('Início')->dateTime('d/m/Y H:i:s'),
                        TextEntry::make('completed_at')->label('Término')->dateTime('d/m/Y H:i:s'),
                    ])->columns(2),

                Section::make('Artefatos Gerados (Arquivos e Metadados)')
                    ->schema([
                        TextEntry::make('artifacts')
                            ->label('Conteúdo JSON Armazenado')
                            ->getStateUsing(fn($record) => json_encode($record->artifacts, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES))
                            ->html()
                            ->formatStateUsing(fn($state) => "<pre class='text-xs bg-gray-100 p-4 rounded-lg overflow-x-auto dark:bg-gray-900 border border-gray-200 dark:border-gray-800' style='max-width:100%;'>{$state}</pre>")
                            ->columnSpanFull(),
                    ])
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListSitemapJobs::route('/'),
            'view' => Pages\ViewSitemapJob::route('/{record}'),
        ];
    }
}
