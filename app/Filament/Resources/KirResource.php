<?php

namespace App\Filament\Resources;

use App\Models\Kir;
use Filament\Forms;
use Filament\Tables;
use Filament\Forms\Form;
use Filament\Tables\Table;
use Filament\Support\RawJs;
use App\Models\PengajuanKir;
use App\Models\PengajuanKirItem;
use Filament\Resources\Resource;
use Illuminate\Support\Collection;
use Filament\Notifications\Notification;
use Illuminate\Database\Eloquent\Builder;
use App\Filament\Resources\KirResource\Pages;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use App\Filament\Resources\KirResource\RelationManagers;
use Filament\Notifications\Actions\Action as NotificationAction;
use App\Filament\Resources\PengajuanKirResource as PengajuanKirResourceFilament;

class KirResource extends Resource
{
    protected static ?string $model = Kir::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';
    protected static ?int $navigationSort = 2;
    protected static ?string $navigationGroup = 'Manajemen Kendaraan';
    protected static ?string $label = 'KIR';
    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Informasi KIR')
                    ->description('Lengkapi data uji kendaraan secara akurat.')
                    ->columns(12)
                    ->schema([
                        Forms\Components\Select::make('stnk_id')
                            ->label('Nomor Polisi (STNK)')
                            ->relationship(
                                'stnk',
                                'nomor_polisi',
                                modifyQueryUsing: fn(\Illuminate\Database\Eloquent\Builder $query) => $query
                                    ->whereNull('deleted_at')
                                    ->doesntHave('kir')
                            )
                            ->getOptionLabelFromRecordUsing(fn(\App\Models\Stnk $record) => "{$record->nomor_polisi} • {$record->merk_kendaraan} {$record->tipe_kendaraan}")
                            ->required()
                            ->searchable()
                            ->preload()
                            ->placeholder('Pilih nomor polisi')
                            ->helperText('Hanya menampilkan STNK aktif yang belum memiliki KIR.')
                            ->columnSpan(6)
                            ->hiddenOn('edit'),

                        Forms\Components\Placeholder::make('nomor_polisi_view')
                            ->label('Nomor Polisi')
                            ->content(fn(?\App\Models\Kir $record) => optional($record->stnk)->nomor_polisi ?? '-')
                            ->hiddenOn('create')
                            ->columnSpan(12),

                        Forms\Components\TextInput::make('nomor_uji_kendaraan')
                            ->label('Nomor Uji Kendaraan')
                            ->placeholder('Contoh: 1234/XYZ/2025')
                            ->prefixIcon('heroicon-o-identification')
                            ->required()
                            ->maxLength(255)
                            ->unique(ignoreRecord: true)
                            ->rule('regex:/^[A-Z0-9\/\.\-\s]+$/i')
                            ->helperText('Gunakan huruf kapital, angka, dan karakter / . -')
                            ->columnSpan(6),

                        Forms\Components\DatePicker::make('masa_berlaku')
                            ->label('Masa Berlaku')
                            ->native(false)
                            ->displayFormat('d mm yy')
                            ->required()
                            ->minDate(now())
                            ->suffixIcon('heroicon-o-calendar')
                            ->helperText('Tanggal mulai berlaku uji KIR.')
                            ->hiddenOn('edit')
                            ->columnSpan(6),

                        Forms\Components\DatePicker::make('masa_berlaku')
                            ->label('Masa Berlaku')
                            ->native(false)
                            ->displayFormat('d mm yy')
                            ->required()
                            ->suffixIcon('heroicon-o-calendar')
                            ->helperText('Tanggal berlaku tercatat. Nilai lama diizinkan saat edit.')
                            ->hiddenOn('create')
                            ->columnSpan(6),
                        // nominal biaya uji
                        Forms\Components\TextInput::make('nominal_biaya_uji')
                            ->label('Biaya Uji (Rp)')
                            ->numeric()
                            ->minValue(0)
                            // ->default(0)
                            ->prefix('Rp ')
                             ->mask(RawJs::make('$money($input)'))
                            ->stripCharacters(',')
                            ->required()
                            ->columnSpan(6),
                    ]),

                Forms\Components\Section::make('Dokumen Pendukung')
                    ->description('Unggah dokumen uji KIR yang relevan.')
                    ->schema([
                        Forms\Components\SpatieMediaLibraryFileUpload::make('attachments')
                            ->label('Lampiran')
                            ->collection('kir_attachments')
                            ->multiple()
                            ->maxFiles(10)
                            ->maxSize(8 * 1024) // 8 MB per file
                            ->acceptedFileTypes(['application/pdf', 'image/jpeg', 'image/png'])
                            ->downloadable()
                            ->openable()
                            ->reorderable()
                            ->helperText('PDF/JPG/PNG, maks 8 MB per file, hingga 10 file.'),
                    ])
                    ->collapsible()
                    ->collapsed(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('masa_berlaku', 'asc')
            ->columns([
                // nomor urut
                Tables\Columns\TextColumn::make('id')
                    ->label('No')
                    ->rowIndex(),

                Tables\Columns\TextColumn::make('stnk.nomor_polisi')
                    ->label('Nomor Polisi')
                    ->searchable()
                    ->copyable()
                    ->tooltip(fn($state) => "Nomor Polisi: {$state}")
                    ->sortable(),

                Tables\Columns\TextColumn::make('nomor_uji_kendaraan')
                    ->label('Nomor Uji')
                    ->searchable()
                    ->copyable()
                    ->sortable(),
                    // nominal biaya uji

                Tables\Columns\TextColumn::make('nominal_biaya_uji')
                    ->label('Biaya Uji')
                    ->formatStateUsing(fn ($state) => 'Rp ' . number_format($state, 0, ',', '.'))
                    ->sortable(),
                    

                Tables\Columns\TextColumn::make('masa_berlaku')
                    ->label('Masa Berlaku')
                    ->date('d M Y')
                    ->badge()
                    ->color(fn($state) => \Illuminate\Support\Carbon::parse($state)->isPast() ? 'danger' : 'success')
                    ->description(fn($state) => \Illuminate\Support\Carbon::parse($state)->diffForHumans())
                    ->sortable(),

                Tables\Columns\TextColumn::make('lampiran')
                    ->label('Lampiran')
                    ->state(fn(\App\Models\Kir $record) => $record->getMedia('kir_attachments')->count() . ' file')
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('deleted_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\TrashedFilter::make(),
                Tables\Filters\Filter::make('expired')
                    ->label('Kedaluwarsa')
                    ->query(fn(Builder $query) => $query->whereDate('masa_berlaku', '<', now())),
                Tables\Filters\Filter::make('expire_soon_30')
                    ->label('Akan Habis 30 Hari')
                    ->query(fn(Builder $query) => $query->whereBetween('masa_berlaku', [now(), now()->addDays(30)])),
            ])
            ->actions([
                Tables\Actions\ActionGroup::make([
                    Tables\Actions\ViewAction::make(),
                    Tables\Actions\EditAction::make(),
                    Tables\Actions\DeleteAction::make(),
                ]),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\BulkAction::make('buat_pengajuan_kir')
                        ->label('Buat Pengajuan KIR dari Terpilih')
                        ->icon('heroicon-o-document-plus')
                        ->requiresConfirmation()
                        ->form([
                            Forms\Components\TextInput::make('admin_fee_default')
                                ->label('Biaya Admin Default per Item (Rp)')
                                ->numeric()
                                ->minValue(0)
                                ->default(0)
                                ->prefix('Rp '),
                        ])
                        ->action(function (Collection $records, array $data): void {
                            if ($records->isEmpty()) {
                                Notification::make()
                                    ->title('Tidak ada KIR terpilih.')
                                    ->danger()
                                    ->send();
                                return;
                            }

                            // Buat dokumen pengajuan KIR draft (nomor & created_by auto via model hooks)
                            $pengajuan = PengajuanKir::create([]);

                            $adminDefault = (int) ($data['admin_fee_default'] ?? 0);

                            $records->each(function (Kir $kir) use ($pengajuan, $adminDefault) {
                                // Abaikan jika KIR terhapus lembut
                                if (! is_null($kir->deleted_at)) {
                                    return;
                                }

                                PengajuanKirItem::create([
                                    'pengajuan_kir_id' => $pengajuan->id,
                                    'kir_id' => $kir->id,
                                    'snapshot_nomor_uji' => $kir->nomor_uji_kendaraan,
                                    'snapshot_masa_berlaku' => $kir->masa_berlaku,
                                    'snapshot_nominal_biaya_uji' => (int) ($kir->nominal_biaya_uji ?? 0),
                                    'admin_fee' => $adminDefault,
                                ]);
                            });

                            // Hitung ulang total setelah penambahan item
                            $pengajuan->recalcTotals();

                            // Notifikasi dengan tombol untuk membuka halaman edit pengajuan KIR
                            $url = PengajuanKirResourceFilament::getUrl('edit', ['record' => $pengajuan]);
                            Notification::make()
                                ->title('Pengajuan KIR dibuat dari KIR terpilih.')
                                ->success()
                                ->body('Klik tombol di bawah untuk membuka dokumen.')
                                ->actions([
                                    NotificationAction::make('Buka')->url($url)->button(),
                                ])
                                ->send();
                        })
                        ->deselectRecordsAfterCompletion(),

                    Tables\Actions\DeleteBulkAction::make(),
                    Tables\Actions\ForceDeleteBulkAction::make(),
                    Tables\Actions\RestoreBulkAction::make(),
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
            'index' => Pages\ListKirs::route('/'),
            'create' => Pages\CreateKir::route('/create'),
            'edit' => Pages\EditKir::route('/{record}/edit'),
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->withoutGlobalScopes([
                SoftDeletingScope::class,
            ]);
    }
}
