<?php

namespace App\Filament\Resources;

use App\Filament\Resources\PengajuanKirResource\Pages;
use App\Models\PengajuanKir;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use App\Filament\Resources\PengajuanKirResource\RelationManagers\ItemsRelationManager;
use App\Filament\Resources\PengajuanKirResource\RelationManagers\LogsRelationManager;

class PengajuanKirResource extends Resource
{
    protected static ?string $model = PengajuanKir::class;

    protected static ?string $navigationIcon = 'heroicon-o-clipboard-document-check';
    protected static ?string $navigationGroup = 'Pengajuan Pajak';
    protected static ?int $navigationSort = 2;
    protected static ?string $label = 'Pengajuan KIR';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Informasi Pengajuan KIR')
                    ->columns(12)
                    ->schema([
                        Forms\Components\Placeholder::make('nomor')
                            ->label('Nomor Dokumen')
                            ->content(fn (?PengajuanKir $record) => $record?->nomor ?? '(otomatis saat simpan)')
                            ->columnSpan(4),

                        Forms\Components\Placeholder::make('status')
                            ->label('Status')
                            ->content(fn (?PengajuanKir $record) => $record?->status ? ucfirst($record->status) : 'Draft')
                            ->columnSpan(4),

                        Forms\Components\Placeholder::make('creator')
                            ->label('Dibuat Oleh')
                            ->content(fn (?PengajuanKir $record) => optional($record?->creator)->name ?? '-')
                            ->columnSpan(4),

                        Forms\Components\Placeholder::make('total_biaya_uji')
                            ->label('Total Biaya Uji')
                            ->content(fn (?PengajuanKir $record) => static::formatRp($record?->total_biaya_uji ?? 0))
                            ->columnSpan(4),

                        Forms\Components\Placeholder::make('total_admin')
                            ->label('Total Admin')
                            ->content(fn (?PengajuanKir $record) => static::formatRp($record?->total_admin ?? 0))
                            ->columnSpan(4),

                        Forms\Components\Placeholder::make('grand_total')
                            ->label('Grand Total')
                            ->content(fn (?PengajuanKir $record) => static::formatRp($record?->grand_total ?? 0))
                            ->columnSpan(4),

                        Forms\Components\Placeholder::make('timestamps')
                            ->label('Timestamps')
                            ->content(function (?PengajuanKir $record) {
                                if (! $record) return '-';
                                $parts = [];
                                if ($record->submitted_at) $parts[] = 'Diajukan: ' . $record->submitted_at->format('d M Y H:i');
                                if ($record->approved_at) $parts[] = 'Disetujui: ' . $record->approved_at->format('d M Y H:i');
                                if ($record->paid_at) $parts[] = 'Dibayar: ' . $record->paid_at->format('d M Y H:i');
                                return $parts ? implode(' | ', $parts) : '-';
                            })
                            ->columnSpan(12),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->columns([
                // nomor urut
                Tables\Columns\TextColumn::make('id')
                    ->label('No.')
                    ->rowIndex(),
                    
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

                Tables\Columns\TextColumn::make('items_count')
                    ->label('Jumlah Item')
                    ->counts('items')
                    ->sortable(),

                Tables\Columns\TextColumn::make('total_biaya_uji')
                    ->label('Total Biaya Uji')
                    ->formatStateUsing(fn ($state) => static::formatRp((int) $state))
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('total_admin')
                    ->label('Total Admin')
                    ->formatStateUsing(fn ($state) => static::formatRp((int) $state))
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('grand_total')
                    ->label('Grand Total')
                    ->formatStateUsing(fn ($state) => static::formatRp((int) $state))
                    ->sortable(),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Dibuat')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('submitted_at')
                    ->label('Diajukan')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('approved_at')
                    ->label('Disetujui')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('paid_at')
                    ->label('Dibayar')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\TrashedFilter::make(),

                Tables\Filters\SelectFilter::make('status')
                    ->label('Status')
                    ->options([
                        'draft' => 'Draft',
                        'diajukan' => 'Diajukan',
                        'disetujui' => 'Disetujui',
                        'ditolak' => 'Ditolak',
                        'dibayar' => 'Dibayar',
                    ]),

                Tables\Filters\Filter::make('created_between')
                    ->label('Dibuat Antara')
                    ->form([
                        Forms\Components\DatePicker::make('from')->label('Dari'),
                        Forms\Components\DatePicker::make('until')->label('Sampai'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when($data['from'] ?? null, fn (Builder $q, $date) => $q->whereDate('created_at', '>=', $date))
                            ->when($data['until'] ?? null, fn (Builder $q, $date) => $q->whereDate('created_at', '<=', $date));
                    }),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                    Tables\Actions\ForceDeleteBulkAction::make(),
                    Tables\Actions\RestoreBulkAction::make(),
                ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            ItemsRelationManager::class,
            LogsRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListPengajuanKirs::route('/'),
            'create' => Pages\CreatePengajuanKir::route('/create'),
            'edit' => Pages\EditPengajuanKir::route('/{record}/edit'),
        ];
    }

    protected static function formatRp(?int $value): string
    {
        $value = (int) ($value ?? 0);
        return 'Rp ' . number_format($value, 0, ',', '.');
    }
}