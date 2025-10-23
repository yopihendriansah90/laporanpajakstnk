<?php

namespace App\Filament\Resources\PengajuanResource\RelationManagers;

use App\Models\Stnk;
use Filament\Forms;
use Filament\Forms\Get;
use Filament\Forms\Set;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Log;

class ItemsRelationManager extends RelationManager
{
    protected static string $relationship = 'items';
    protected static ?string $title = 'Items (STNK)';
    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Item Pengajuan')
                    ->columns(12)
                    ->schema([
                        Forms\Components\Select::make('stnk_id')
                            ->label('Nomor Polisi (STNK)')
                            ->searchable()
                            ->preload()
                            ->options(fn () => Stnk::query()
                                ->whereNull('deleted_at')
                                ->orderBy('nomor_polisi')
                                ->pluck('nomor_polisi', 'id')
                                ->toArray()
                            )
                            ->required()
                             // Unik per pengajuan (tidak boleh duplikat STNK dalam 1 dokumen) + pesan kustom
                             ->rules([
                                 fn (self $livewire) => function (string $attribute, $value, \Closure $fail) use ($livewire) {
                                     if (! $value) {
                                         return;
                                     }

                                     $owner = $livewire->getOwnerRecord();
                                     if (! $owner) {
                                         Log::warning('ItemsRelationManager.rules: owner record null');
                                         return;
                                     }

                                     $pengajuanId = $owner->id;
                                     $currentId = $livewire->getMountedTableActionRecord()?->id;

                                     $exists = \App\Models\PengajuanItem::query()
                                         ->where('pengajuan_id', $pengajuanId)
                                         ->where('stnk_id', (int) $value)
                                         ->when($currentId, fn ($q) => $q->where('id', '!=', $currentId))
                                         ->exists();

                                     Log::debug('ItemsRelationManager.rules duplicate check', [
                                         'pengajuan_id' => $pengajuanId,
                                         'stnk_id' => (int) $value,
                                         'current_id' => $currentId,
                                         'exists' => $exists,
                                     ]);

                                     if ($exists) {
                                         $fail('Nomor polisi sudah ditambahkan dalam pengajuan ini.');
                                     }
                                 },
                             ])
                            ->validationAttribute('Nomor Polisi (STNK)')
                            ->afterStateHydrated(function (Get $get, Set $set): void {
                                $id = $get('stnk_id');
                                if (! $id) return;
                                $stnk = Stnk::withTrashed()->find($id);
                                $set('snapshot_nomor_polisi', $stnk?->nomor_polisi ?? null);
                                $set('snapshot_nama_pemilik', $stnk?->nama_pemilik ?? null);
                                $set('snapshot_nominal_pokok_pajak', (int) ($stnk?->nominal_pokok_pajak ?? 0));
                                // isi view fields (read-only)
                                $set('view_merk_kendaraan', $stnk?->merk_kendaraan ?? null);
                                $set('view_tipe_kendaraan', $stnk?->tipe_kendaraan ?? null);
                                $set('view_jenis_kendaraan', $stnk?->jenis_kendaraan ?? null);
                                $set('view_model_kendaraan', $stnk?->model_kendaraan ?? null);
                                $set('view_tahun_pembuatan', $stnk?->tahun_pembuatan ? (string) $stnk->tahun_pembuatan : null);
                                $set('view_warna_kendaraan', $stnk?->warna_kendaraan ?? null);
                                $set('view_nomor_rangka', $stnk?->nomor_rangka ?? null);
                                $set('view_nomor_mesin', $stnk?->nomor_mesin ?? null);
                                $set('view_kapasitas_silinder', $stnk?->kapasitas_silinder ? (string) $stnk->kapasitas_silinder : null);
                            })
                            ->afterStateUpdated(function ($state, Set $set): void {
                                $stnk = Stnk::withTrashed()->find($state);

                                $set('snapshot_nomor_polisi', $stnk?->nomor_polisi ?? null);
                                $set('snapshot_nama_pemilik', $stnk?->nama_pemilik ?? null);
                                $set('snapshot_nominal_pokok_pajak', (int) ($stnk?->nominal_pokok_pajak ?? 0));
                                // isi view fields (read-only)
                                $set('view_merk_kendaraan', $stnk?->merk_kendaraan ?? null);
                                $set('view_tipe_kendaraan', $stnk?->tipe_kendaraan ?? null);
                                $set('view_jenis_kendaraan', $stnk?->jenis_kendaraan ?? null);
                                $set('view_model_kendaraan', $stnk?->model_kendaraan ?? null);
                                $set('view_tahun_pembuatan', $stnk?->tahun_pembuatan ? (string) $stnk->tahun_pembuatan : null);
                                $set('view_warna_kendaraan', $stnk?->warna_kendaraan ?? null);
                                $set('view_nomor_rangka', $stnk?->nomor_rangka ?? null);
                                $set('view_nomor_mesin', $stnk?->nomor_mesin ?? null);
                                $set('view_kapasitas_silinder', $stnk?->kapasitas_silinder ? (string) $stnk->kapasitas_silinder : null);
                            })
                            ->disabled(fn (self $livewire) => $livewire->getOwnerRecord()->status !== 'draft')
                            ->columnSpan(4),

                        Forms\Components\TextInput::make('snapshot_nomor_polisi')
                            ->label('Snapshot Nomor Polisi')
                            ->disabled()
                            ->dehydrated(true)
                            ->columnSpan(4),

                        Forms\Components\TextInput::make('snapshot_nama_pemilik')
                            ->label('Snapshot Nama Pemilik')
                            ->disabled()
                            ->dehydrated(true)
                            ->columnSpan(4),

                        Forms\Components\TextInput::make('snapshot_nominal_pokok_pajak')
                            ->label('Snapshot Pokok Pajak (Rp)')
                            ->numeric()
                            ->prefix('Rp ')
                            ->disabled()
                            ->dehydrated(true)
                            ->columnSpan(4),

                        // Informasi tambahan STNK (read-only, tidak disimpan)
                        Forms\Components\TextInput::make('view_merk_kendaraan')
                            ->label('Merk')
                            ->disabled()
                            ->dehydrated(false)
                            ->columnSpan(4),

                        Forms\Components\TextInput::make('view_tipe_kendaraan')
                            ->label('Tipe')
                            ->disabled()
                            ->dehydrated(false)
                            ->columnSpan(4),

                        Forms\Components\TextInput::make('view_jenis_kendaraan')
                            ->label('Jenis')
                            ->disabled()
                            ->dehydrated(false)
                            ->columnSpan(4),

                        Forms\Components\TextInput::make('view_model_kendaraan')
                            ->label('Model')
                            ->disabled()
                            ->dehydrated(false)
                            ->columnSpan(4),

                        Forms\Components\TextInput::make('view_tahun_pembuatan')
                            ->label('Tahun Pembuatan')
                            ->disabled()
                            ->dehydrated(false)
                            ->columnSpan(4),

                        Forms\Components\TextInput::make('view_warna_kendaraan')
                            ->label('Warna')
                            ->disabled()
                            ->dehydrated(false)
                            ->columnSpan(4),

                        Forms\Components\TextInput::make('view_nomor_rangka')
                            ->label('Nomor Rangka')
                            ->disabled()
                            ->dehydrated(false)
                            ->columnSpan(4),

                        Forms\Components\TextInput::make('view_nomor_mesin')
                            ->label('Nomor Mesin')
                            ->disabled()
                            ->dehydrated(false)
                            ->columnSpan(4),

                        Forms\Components\TextInput::make('view_kapasitas_silinder')
                            ->label('Kapasitas Silinder (cc)')
                            ->disabled()
                            ->dehydrated(false)
                            ->columnSpan(4),

                        Forms\Components\Placeholder::make('masa_berlaku_1_info')
                            ->label('Berlaku 1 Tahun')
                            ->content(function (Get $get) {
                                $stnk = \App\Models\Stnk::withTrashed()->find($get('stnk_id'));
                                if (! $stnk?->masa_berlaku_1) return '-';
                                $d = \Illuminate\Support\Carbon::parse($stnk->masa_berlaku_1);
                                return $d->format('d M Y') . ' (' . $d->diffForHumans() . ')';
                            })
                            ->columnSpan(4),

                        Forms\Components\Placeholder::make('masa_berlaku_5_info')
                            ->label('Berlaku 5 Tahun')
                            ->content(function (Get $get) {
                                $stnk = \App\Models\Stnk::withTrashed()->find($get('stnk_id'));
                                if (! $stnk?->masa_berlaku_5) return '-';
                                $d = \Illuminate\Support\Carbon::parse($stnk->masa_berlaku_5);
                                return $d->format('d M Y') . ' (' . $d->diffForHumans() . ')';
                            })
                            ->columnSpan(4),

                        Forms\Components\TextInput::make('admin_fee')
                            ->label('Biaya Admin (Rp)')
                            ->numeric()
                            ->minValue(0)
                            ->default(0)
                            ->prefix('Rp ')
                            ->reactive()
                            ->afterStateUpdated(function (Set $set, Get $get) {
                                $subtotal = (int) ($get('snapshot_nominal_pokok_pajak') ?? 0) + (int) ($get('admin_fee') ?? 0);
                                $set('subtotal_calc', $subtotal);
                            })
                            ->disabled(fn (self $livewire) => $livewire->getOwnerRecord()->status !== 'draft')
                            ->columnSpan(4),

                        Forms\Components\Placeholder::make('subtotal_calc')
                            ->label('Subtotal (Rp)')
                            ->content(fn (Get $get) => static::formatRp(
                                (int) ($get('snapshot_nominal_pokok_pajak') ?? 0) + (int) ($get('admin_fee') ?? 0)
                            ))
                            ->columnSpan(4)
                            ->dehydrated(false),
                    ]),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('snapshot_nomor_polisi')
            ->columns([
                // nomor urut
                Tables\Columns\TextColumn::make('id')
                    ->label('No.')
                    ->rowIndex(),
                    
                Tables\Columns\TextColumn::make('snapshot_nomor_polisi')
                    ->label('Nomor Polisi')
                    ->searchable(),

                Tables\Columns\TextColumn::make('snapshot_nama_pemilik')
                    ->label('Nama Pemilik')
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->searchable(),

                Tables\Columns\TextColumn::make('snapshot_nominal_pokok_pajak')
                    ->label('Pokok Pajak')
                    ->formatStateUsing(fn ($state) => static::formatRp((int) $state))
                    ->sortable(),

                Tables\Columns\TextColumn::make('admin_fee')
                    ->label('Admin Fee')
                    ->formatStateUsing(fn ($state) => static::formatRp((int) $state))
                    ->sortable(),

                Tables\Columns\TextColumn::make('subtotal')
                    ->label('Subtotal')
                    ->formatStateUsing(fn ($state) => static::formatRp((int) $state))
                    ->sortable(),

                Tables\Columns\TextColumn::make('stnk.masa_berlaku_1')
                    ->label('Berlaku 1 Tahun')
                    ->date('d M Y')
                    ->badge()
                    ->color(fn($state) => \Illuminate\Support\Carbon::parse($state)->isPast() ? 'danger' : 'success')
                    ->description(fn($state) => \Illuminate\Support\Carbon::parse($state)->diffForHumans())
                    ->sortable(),

                Tables\Columns\TextColumn::make('stnk.masa_berlaku_5')
                    ->label('Berlaku 5 Tahun')
                    ->date('d M Y')
                    ->badge()
                    ->color(fn($state) => \Illuminate\Support\Carbon::parse($state)->isPast() ? 'danger' : 'success')
                    ->description(fn($state) => \Illuminate\Support\Carbon::parse($state)->diffForHumans())
                    ->sortable(),
            ])
            ->headerActions([
                Tables\Actions\CreateAction::make()
                    ->visible(fn () => $this->canCreate())
                    ->after(fn () => $this->notifyParentToRefresh()),
            ])
            ->actions([
                Tables\Actions\EditAction::make()
                    ->visible(fn (Model $record) => $this->canEdit($record))
                    ->after(fn () => $this->notifyParentToRefresh()),
                Tables\Actions\DeleteAction::make()
                    ->visible(fn (Model $record) => $this->canDelete($record))
                    ->after(fn () => $this->notifyParentToRefresh()),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make()
                        ->visible(fn () => $this->canCreate()) // hanya saat draft
                        ->after(fn () => $this->notifyParentToRefresh()),
                ]),
            ]);
    }

    protected function canCreate(): bool
    {
        return $this->getOwnerRecord()->status === 'draft';
    }

    protected function canEdit(Model $record): bool
    {
        return $this->getOwnerRecord()->status === 'draft';
    }

    protected function canDelete(Model $record): bool
    {
        return $this->getOwnerRecord()->status === 'draft';
    }

    private function notifyParentToRefresh(): void
    {
        $owner = $this->getOwnerRecord();
        if ($owner) {
            $owner->refresh();
        }
        // Broadcast event untuk didengarkan oleh EditPengajuan (parent page)
        $this->dispatch('pengajuan-items-updated');
    }

    protected static function formatRp(?int $value): string
    {
        $value = (int) ($value ?? 0);
        return 'Rp ' . number_format($value, 0, ',', '.');
    }
}