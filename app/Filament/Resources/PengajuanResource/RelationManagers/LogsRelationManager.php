<?php

namespace App\Filament\Resources\PengajuanResource\RelationManagers;

use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;

class LogsRelationManager extends RelationManager
{
    protected static string $relationship = 'logs';
    protected static ?string $title = 'Log Status';

    public function form(Form $form): Form
    {
        // Read-only: tidak ada form input
        return $form->schema([]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->columns([
                Tables\Columns\TextColumn::make('status_from')
                    ->label('Dari')
                    ->badge()
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('status_to')
                    ->label('Ke')
                    ->badge(),

                Tables\Columns\TextColumn::make('message')
                    ->label('Pesan')
                    ->wrap()
                    ->limit(120)
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('user.name')
                    ->label('User'),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Waktu')
                    ->dateTime()
                    ->sortable(),
            ])
            ->headerActions([
                // Read-only: tanpa create
            ])
            ->actions([
                // Read-only: tanpa edit/delete
            ])
            ->bulkActions([
                // Read-only: tanpa bulk actions
            ]);
    }
}