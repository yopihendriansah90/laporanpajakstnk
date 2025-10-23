<?php

namespace App\Filament\Resources;

use Filament\Forms;
use App\Models\Stnk;
use Filament\Tables;
use Filament\Forms\Form;
use App\Models\Pengajuan;
use Filament\Tables\Table;
use Filament\Support\RawJs;
use App\Models\PengajuanItem;
use Filament\Resources\Resource;
use Illuminate\Support\Collection;
use Filament\Notifications\Notification;
use Illuminate\Database\Eloquent\Builder;
use App\Filament\Resources\StnkResource\Pages;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use App\Filament\Resources\StnkResource\RelationManagers;
use Filament\Notifications\Actions\Action as NotificationAction;
use App\Filament\Resources\PengajuanResource as PengajuanResourceFilament;

class StnkResource extends Resource
{
    protected static ?string $model = Stnk::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';
    // urutan pertama di menu 
    protected static ?int $navigationSort = 1;
    protected static ?string $navigationGroup = 'Manajemen Kendaraan';
    // label "KIR"
    protected static ?string $label = 'STNK';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Identitas Kendaraan')
                    ->description('Lengkapi data identitas STNK.')
                    ->columns(12)
                    ->schema([
                        Forms\Components\TextInput::make('nomor_polisi')
                            ->label('Nomor Polisi')
                            ->placeholder('Contoh: B 1234 ABC')
                            ->required()
                            ->unique(ignoreRecord: true)
                            ->maxLength(255)
                            ->validationAttribute('Nomor Polisi')
                            ->helperText('Harus unik per STNK.')
                            ->columnSpan(6),

                        Forms\Components\TextInput::make('nama_pemilik')
                            ->label('Nama Pemilik')
                            ->required()
                            ->maxLength(255)
                            ->columnSpan(6),

                        Forms\Components\Textarea::make('alamat_pemilik')
                            ->label('Alamat Pemilik')
                            ->rows(2)
                            ->maxLength(255)
                            ->helperText('Maksimal 255 karakter.')
                            ->columnSpan(12),
                    ]),

                Forms\Components\Section::make('Spesifikasi Kendaraan')
                    ->columns(12)
                    ->schema([
                        Forms\Components\TextInput::make('merk_kendaraan')
                            ->label('Merk')
                            ->maxLength(255)
                            ->columnSpan(4),

                        Forms\Components\TextInput::make('tipe_kendaraan')
                            ->label('Tipe')
                            ->maxLength(255)
                            ->columnSpan(4),

                        Forms\Components\TextInput::make('jenis_kendaraan')
                            ->label('Jenis')
                            ->maxLength(255)
                            ->columnSpan(4),

                        Forms\Components\TextInput::make('model_kendaraan')
                            ->label('Model')
                            ->maxLength(255)
                            ->columnSpan(4),

                        Forms\Components\TextInput::make('tahun_pembuatan')
                            ->label('Tahun Pembuatan')
                            ->numeric()
                            ->minValue(1900)
                            ->maxValue((int) date('Y'))
                            ->columnSpan(4),

                        Forms\Components\TextInput::make('warna_kendaraan')
                            ->label('Warna')
                            ->maxLength(255)
                            ->columnSpan(4),

                        Forms\Components\TextInput::make('nomor_rangka')
                            ->label('Nomor Rangka')
                            ->maxLength(255)
                            ->columnSpan(6),

                        Forms\Components\TextInput::make('nomor_mesin')
                            ->label('Nomor Mesin')
                            ->maxLength(255)
                            ->columnSpan(6),

                        Forms\Components\TextInput::make('kapasitas_silinder')
                            ->label('Kapasitas Silinder (cc)')
                            ->numeric()
                            ->minValue(1)
                            ->columnSpan(4),

                        Forms\Components\TextInput::make('nominal_pokok_pajak')
                            ->label('Nominal Pokok Pajak (Rp)')
                            ->numeric()
                            ->mask(RawJs::make('$money($input)'))
                            ->stripCharacters(',')
                            ->prefix('Rp')
                            ->minValue(0)
                            ->default(0)
                            ->columnSpan(4),

                    ]),

                Forms\Components\Section::make('Masa Berlaku')
                    ->columns(12)
                    ->schema([
                        // Khusus create: wajib >= hari ini
                        Forms\Components\DatePicker::make('masa_berlaku_1')
                            ->label('Masa Berlaku 1 Tahun')
                            ->native(false)
                            ->displayFormat('d MMM yyyy')
                            ->required()
                            ->minDate(now())
                            ->helperText('Tanggal mulai berlaku tahunan.')
                            ->hiddenOn('edit')
                            ->columnSpan(6),

                        Forms\Components\DatePicker::make('masa_berlaku_1')
                            ->label('Masa Berlaku 1 Tahun')
                            ->native(false)
                            ->displayFormat('d MMM yyyy')
                            ->required()
                            ->helperText('Tanggal berlaku tercatat. Nilai lama diizinkan saat edit.')
                            ->hiddenOn('create')
                            ->columnSpan(6),

                        Forms\Components\DatePicker::make('masa_berlaku_5')
                            ->label('Masa Berlaku 5 Tahun')
                            ->native(false)
                            ->displayFormat('d MMM yyyy')
                            ->required()
                            ->minDate(now())
                            ->helperText('Tanggal mulai berlaku 5 tahunan.')
                            ->hiddenOn('edit')
                            ->columnSpan(6),

                        Forms\Components\DatePicker::make('masa_berlaku_5')
                            ->label('Masa Berlaku 5 Tahun')
                            ->native(false)
                            ->displayFormat('d MMM yyyy')
                            ->required()
                            ->helperText('Tanggal berlaku tercatat. Nilai lama diizinkan saat edit.')
                            ->hiddenOn('create')
                            ->columnSpan(6),
                    ]),

                Forms\Components\Section::make('Dokumen Pendukung')
                    ->description('Unggah dokumen STNK, foto kendaraan, dan berkas terkait.')
                    ->schema([
                        Forms\Components\SpatieMediaLibraryFileUpload::make('attachments')
                            ->label('Lampiran')
                            ->collection('stnk_attachments')
                            ->disk('public')
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
            ->defaultSort('masa_berlaku_1', 'asc')
            ->columns([
                // nomor urut
                Tables\Columns\TextColumn::make('id')
                    ->label('No')
                    ->rowIndex(),

                Tables\Columns\TextColumn::make('nomor_polisi')
                    ->label('Nomor Polisi')
                    ->searchable()
                    ->copyable(),

                Tables\Columns\TextColumn::make('nama_pemilik')
                    ->label('Nama Pemilik')
                    ->searchable(),

                Tables\Columns\TextColumn::make('merk_kendaraan')
                    ->label('Merk')
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('tipe_kendaraan')
                    ->label('Tipe')
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('jenis_kendaraan')
                    ->label('Jenis')
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('model_kendaraan')
                    ->label('Model')
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('tahun_pembuatan')
                    ->label('Tahun')
                    ->numeric()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('warna_kendaraan')
                    ->label('Warna')
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('kapasitas_silinder')
                    ->label('CC')
                    ->numeric()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('masa_berlaku_1')
                    ->label('Berlaku 1 Tahun')
                    ->date('d M Y')
                    ->sortable()
                    ->badge()
                    ->color(fn($state) => \Illuminate\Support\Carbon::parse($state)->isPast() ? 'danger' : 'success')
                    ->description(fn($state) => \Illuminate\Support\Carbon::parse($state)->diffForHumans()),

                Tables\Columns\TextColumn::make('masa_berlaku_5')
                    ->label('Berlaku 5 Tahun')
                    ->date('d M Y')
                    ->sortable()
                    ->badge()
                    ->color(fn($state) => \Illuminate\Support\Carbon::parse($state)->isPast() ? 'danger' : 'success')
                    ->description(fn($state) => \Illuminate\Support\Carbon::parse($state)->diffForHumans()),

                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\TrashedFilter::make(),
                Tables\Filters\Filter::make('expired_1')
                    ->label('1 Tahun Kedaluwarsa')
                    ->query(fn(Builder $query) => $query->whereDate('masa_berlaku_1', '<', now())),
                Tables\Filters\Filter::make('expire_soon_30')
                    ->label('Akan Habis 30 Hari (1 Tahun)')
                    ->query(fn(Builder $query) => $query->whereBetween('masa_berlaku_1', [now(), now()->addDays(30)])),
                Tables\Filters\Filter::make('expired_5')
                    ->label('5 Tahun Kedaluwarsa')
                    ->query(fn(Builder $query) => $query->whereDate('masa_berlaku_5', '<', now())),
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
                    Tables\Actions\BulkAction::make('buat_pengajuan')
                        ->label('Buat Pengajuan dari Terpilih')
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
                                    ->title('Tidak ada STNK terpilih.')
                                    ->danger()
                                    ->send();
                                return;
                            }

                            // Buat dokumen pengajuan draft (nomor & created_by auto via model hooks)
                            $pengajuan = Pengajuan::create([]);

                            $adminDefault = (int) ($data['admin_fee_default'] ?? 0);

                            $records->each(function (Stnk $stnk) use ($pengajuan, $adminDefault) {
                                // Abaikan jika STNK terhapus lembut
                                if (! is_null($stnk->deleted_at)) {
                                    return;
                                }

                                PengajuanItem::create([
                                    'pengajuan_id' => $pengajuan->id,
                                    'stnk_id' => $stnk->id,
                                    'snapshot_nomor_polisi' => $stnk->nomor_polisi,
                                    'snapshot_nama_pemilik' => $stnk->nama_pemilik,
                                    'snapshot_nominal_pokok_pajak' => (int) ($stnk->nominal_pokok_pajak ?? 0),
                                    'admin_fee' => $adminDefault,
                                    // subtotal dihitung otomatis oleh model saat saving
                                ]);
                            });

                            // Hitung ulang total setelah penambahan item
                            $pengajuan->recalcTotals();

                            // Buat notifikasi dengan tombol untuk membuka halaman edit pengajuan
                            $url = PengajuanResourceFilament::getUrl('edit', ['record' => $pengajuan]);
                            Notification::make()
                                ->title('Pengajuan dibuat dari STNK terpilih.')
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
            'index' => Pages\ListStnks::route('/'),
            'create' => Pages\CreateStnk::route('/create'),
            'edit' => Pages\EditStnk::route('/{record}/edit'),
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
