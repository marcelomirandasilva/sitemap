<?php

namespace App\Filament\Resources;

use App\Filament\Resources\PlanResource\Pages;
use App\Filament\Resources\PlanResource\RelationManagers;
use App\Models\Plano;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class PlanResource extends Resource
{
    protected static ?string $model = Plano::class;

    protected static ?string $navigationIcon = 'heroicon-o-credit-card';

    protected static ?string $navigationLabel = 'Planos';
    protected static ?string $pluralLabel = 'Planos';
    protected static ?string $modelLabel = 'Plano';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Informações Gerais')->schema([
                    Forms\Components\TextInput::make('name')
                        ->required()
                        ->label('Nome do Plano')
                        ->maxLength(255),
                    Forms\Components\TextInput::make('slug')
                        ->required()
                        ->maxLength(255),
                    Forms\Components\TextInput::make('ideal_for')
                        ->label('Ideal para')
                        ->maxLength(255),
                ])->columns(2),

                Forms\Components\Section::make('Limites do Plano')->schema([
                    Forms\Components\TextInput::make('max_pages')
                        ->required()
                        ->label('Máx Páginas')
                        ->numeric(),
                    Forms\Components\TextInput::make('max_projects')
                        ->required()
                        ->label('Máx Projetos')
                        ->numeric()
                        ->default(10),
                    Forms\Components\TextInput::make('update_frequency')
                        ->label('Frequência de Att')
                        ->maxLength(255),
                    Forms\Components\Toggle::make('has_advanced_features')
                        ->label('Possui Recursos Avançados')
                        ->default(false),
                ])->columns(3),

                Forms\Components\Section::make('Stripe e Preços (Centavos)')->schema([
                    Forms\Components\TextInput::make('stripe_monthly_price_id')
                        ->label('Stripe Price ID (Mensal)')
                        ->maxLength(255),
                    Forms\Components\TextInput::make('stripe_yearly_price_id')
                        ->label('Stripe Price ID (Anual)')
                        ->maxLength(255),
                    Forms\Components\TextInput::make('price_monthly_brl')
                        ->label('Preço Mensal (BRL)')
                        ->numeric(),
                    Forms\Components\TextInput::make('price_yearly_brl')
                        ->label('Preço Anual (BRL)')
                        ->numeric(),
                    Forms\Components\TextInput::make('price_monthly_usd')
                        ->label('Preço Mensal (USD)')
                        ->numeric(),
                    Forms\Components\TextInput::make('price_yearly_usd')
                        ->label('Preço Anual (USD)')
                        ->numeric(),
                ])->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label('Nome')
                    ->searchable(),
                Tables\Columns\TextColumn::make('slug')
                    ->searchable(),
                Tables\Columns\TextColumn::make('max_projects')
                    ->label('Máx Projetos')
                    ->sortable(),
                Tables\Columns\TextColumn::make('max_pages')
                    ->label('Máx Páginas')
                    ->sortable(),
                Tables\Columns\IconColumn::make('has_advanced_features')
                    ->label('Avançado')
                    ->boolean(),
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
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListPlans::route('/'),
            'create' => Pages\CreatePlan::route('/create'),
            'edit' => Pages\EditPlan::route('/{record}/edit'),
        ];
    }
}
