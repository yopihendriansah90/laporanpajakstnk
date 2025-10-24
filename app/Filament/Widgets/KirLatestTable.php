<?php

namespace App\Filament\Widgets;

use App\Filament\Resources\PengajuanKirResource;
use App\Models\PengajuanKir;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget;

class KirLatestTable extends TableWidget
{
    protected static ?string $heading = 'Pengajuan KIR Terbaru';

    // Tampilkan berdampingan (kanan) dengan tabel STNK pada layar sedang hingga besar
    protected int|string|array $columnSpan = [
        'md' => 6,
        'xl' => 6,
        '2xl' => 6,
    ];

    public function table(Table $table): Table
    {
        return $table
            ->query(
                PengajuanKir::query()
                    ->withCount('items')
                    ->latest('created_at')
            )
            ->defaultSort('created_at', 'desc')
            ->columns([
                Tables\Columns\TextColumn::make('nomor')
                    ->label('Nomor')
                    ->searchable()
                    ->copyable(),

                Tables\Columns\TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->color(function (string $state) {
                        return match ($state) {
                            'draft' => 'gray',
                            'diajukan' => 'warning',
                            'disetujui' => 'success',
                            'ditolak' => 'danger',
                            'dibayar' => 'success',
                            default => 'gray',
                        };
                    })
                    ->sortable(),

                Tables\Columns\TextColumn::make('submitted_at')
                    ->label('Waktu Diajukan')
                    ->dateTime('d M Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: false),

                Tables\Columns\TextColumn::make('grand_total')
                    ->label('Grand Total')
                    ->formatStateUsing(fn ($state) => 'Rp ' . number_format((int) $state, 0, ',', '.'))
                    ->sortable(),

                Tables\Columns\TextColumn::make('items_count')
                    ->label('Jumlah Item')
                    ->sortable(),
            ])
            ->actions([
                Tables\Actions\Action::make('edit')
                    ->label('Edit')
                    ->icon('heroicon-o-pencil-square')
                    ->url(fn (PengajuanKir $record) => PengajuanKirResource::getUrl('edit', ['record' => $record]))
                    ->openUrlInNewTab(),
            ])
            ->paginated([10, 25, 50])
            ->defaultPaginationPageOption(10);
    }
}