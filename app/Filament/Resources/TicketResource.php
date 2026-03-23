<?php

namespace App\Filament\Resources;

use App\Filament\Resources\TicketResource\Pages;
use App\Filament\Resources\TicketResource\RelationManagers;
use App\Models\Ticket;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class TicketResource extends Resource
{
    protected static ?string $model = Ticket::class;

    protected static ?string $navigationIcon = 'heroicon-o-ticket';

    protected static ?string $navigationLabel = 'Suporte (Tickets)';
    protected static ?string $pluralLabel = 'Tickets';
    protected static ?string $modelLabel = 'Ticket';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Select::make('user_id')
                    ->label('Usuário')
                    ->relationship('usuario', 'name')
                    ->disabled(),
                Forms\Components\Select::make('projeto_id')
                    ->label('Projeto')
                    ->relationship('projeto', 'name')
                    ->disabled(),
                Forms\Components\TextInput::make('titulo')
                    ->label('Título')
                    ->disabled()
                    ->columnSpanFull(),
                Forms\Components\Textarea::make('mensagem')
                    ->label('Mensagem Inicial')
                    ->disabled()
                    ->columnSpanFull(),
                Forms\Components\Select::make('status')
                    ->label('Status')
                    ->options([
                        'aberto' => 'Aberto',
                        'em_analise' => 'Em Análise',
                        'em_atendimento' => 'Em Atendimento',
                        'respondido' => 'Respondido',
                        'aguardando_usuario' => 'Aguardando Usuário',
                        'fechado' => 'Fechado',
                    ])
                    ->required(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('titulo')
                    ->label('Título')
                    ->searchable(),
                Tables\Columns\TextColumn::make('usuario.name')
                    ->label('Usuário')
                    ->sortable()
                    ->searchable(),
                Tables\Columns\TextColumn::make('projeto.name')
                    ->label('Projeto')
                    ->sortable()
                    ->searchable()
                    ->placeholder('-'),
                Tables\Columns\TextColumn::make('status')
                    ->badge()
                    ->color(fn(string $state): string => match ($state) {
                        'aberto' => 'primary',
                        'em_analise', 'em_atendimento' => 'warning',
                        'respondido' => 'success',
                        'aguardando_usuario' => 'gray',
                        'fechado' => 'danger',
                        default => 'secondary',
                    })
                    ->formatStateUsing(fn(string $state): string => ucfirst(str_replace('_', ' ', $state))),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Data de Criação')
                    ->dateTime('d/m/Y H:i')
                    ->sortable(),
            ])
            ->filters([
                //
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            RelationManagers\RespostasRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListTickets::route('/'),
            'create' => Pages\CreateTicket::route('/create'),
            'edit' => Pages\EditTicket::route('/{record}/edit'),
        ];
    }
}
