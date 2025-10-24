<?php

namespace App\Filament\Resources\PengajuanKirResource\RelationManagers;

use App\Models\Kir;
use App\Models\PengajuanKirItem;
use Filament\Forms;
use Filament\Forms\Get;
use Filament\Forms\Set;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Log;

class ItemsRelationManager extends RelationManager
{
    protected static string $relationship = 'items';
    protected static ?string $title = 'Items (KIR)';

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Item Pengajuan KIR')
                    ->columns(12)
                    ->schema([
                        Forms\Components\Select::make('kir_id')
                            ->label('Nomor Uji Kendaraan (KIR)')
                            ->searchable()
                            ->preload()
                            ->options(fn () => Kir::query()
                                ->whereNull('deleted_at')
                                ->orderBy('nomor_uji_kendaraan')
                                ->pluck('nomor_uji_kendaraan', 'id')
                                ->toArray()
                            )
                            ->required()
                            // Unik per pengajuan KIR (tidak boleh duplikat KIR dalam 1 dokumen)
                            ->rules([
                                fn (self $livewire) => function (string $attribute, $value, \Closure $fail) use ($livewire) {
                                    if (! $value) {
                                        return;
                                    }

                                    $owner = $livewire->getOwnerRecord();
                                    if (! $owner) {
                                        Log::warning('PengajuanKir ItemsRelationManager.rules: owner record null');
                                        return;
                                    }

                                    $pengajuanKirId = $owner->id;
                                    $currentId = $livewire->getMountedTableActionRecord()?->id;

                                    $exists = PengajuanKirItem::query()
                                        ->where('pengajuan_kir_id', $pengajuanKirId)
                                        ->where('kir_id', (int) $value)
                                        ->when($currentId, fn ($q) => $q->where('id', '!=', $currentId))
                                        ->exists();

                                    Log::debug('PengajuanKir ItemsRelationManager.rules duplicate check', [
                                        'pengajuan_kir_id' => $pengajuanKirId,
                                        'kir_id' => (int) $value,
                                        'current_id' => $currentId,
                                        'exists' => $exists,
                                    ]);

                                    if ($exists) {
                                        $fail('Nomor uji kendaraan sudah ditambahkan dalam pengajuan KIR ini.');
                                    }
                                },
                            ])
                            ->validationAttribute('Nomor Uji Kendaraan (KIR)')
                            ->afterStateHydrated(function (Get $get, Set $set): void {
                                $id = $get('kir_id');
                                if (! $id) return;
                                $kir = Kir::withTrashed()->find($id);
                                $set('snapshot_nomor_uji', $kir?->nomor_uji_kendaraan ?? null);
                                $set('snapshot_masa_berlaku', $kir?->masa_berlaku ?? null);
                                $set('snapshot_nominal_biaya_uji', (int) ($kir?->nominal_biaya_uji ?? 0));
                                // view fields (read-only)
                                $set('view_nomor_polisi', optional($kir?->stnk)->nomor_polisi);
                            })
                            ->afterStateUpdated(function ($state, Set $set): void {
                                $kir = Kir::withTrashed()->find($state);
                                $set('snapshot_nomor_uji', $kir?->nomor_uji_kendaraan ?? null);
                                $set('snapshot_masa_berlaku', $kir?->masa_berlaku ?? null);
                                $set('snapshot_nominal_biaya_uji', (int) ($kir?->nominal_biaya_uji ?? 0));
                                // view fields (read-only)
                                $set('view_nomor_polisi', optional($kir?->stnk)->nomor_polisi);
                            })
                            ->disabled(fn (self $livewire) => $livewire->getOwnerRecord()->status !== 'draft')
                            ->columnSpan(4),

                        Forms\Components\TextInput::make('snapshot_nomor_uji')
                            ->label('Snapshot Nomor Uji')
                            ->disabled()
                            ->dehydrated(true)
                            ->columnSpan(4),

                        Forms\Components\DatePicker::make('snapshot_masa_berlaku')
                            ->label('Snapshot Masa Berlaku KIR')
                            ->native(false)
                            ->displayFormat('d MMM yyyy')
                            ->disabled()
                            ->dehydrated(true)
                            ->columnSpan(4),

                        Forms\Components\TextInput::make('snapshot_nominal_biaya_uji')
                            ->label('Snapshot Biaya Uji (Rp)')
                            ->numeric()
                            ->prefix('Rp ')
                            ->disabled()
                            ->dehydrated(true)
                            ->columnSpan(4),

                        // Informasi tambahan KIR/STNK (read-only, tidak disimpan)
                        Forms\Components\TextInput::make('view_nomor_polisi')
                            ->label('Nomor Polisi (STNK)')
                            ->disabled()
                            ->dehydrated(false)
                            ->columnSpan(4),

                        Forms\Components\TextInput::make('admin_fee')
                            ->label('Biaya Admin (Rp)')
                            ->numeric()
                            ->minValue(0)
                            ->default(0)
                            ->prefix('Rp ')
                            ->reactive()
                            ->afterStateUpdated(function (Set $set, Get $get) {
                                $subtotal = (int) ($get('snapshot_nominal_biaya_uji') ?? 0) + (int) ($get('admin_fee') ?? 0);
                                $set('subtotal_calc', $subtotal);
                            })
                            ->disabled(fn (self $livewire) => $livewire->getOwnerRecord()->status !== 'draft')
                            ->columnSpan(4),

                        Forms\Components\Placeholder::make('subtotal_calc')
                            ->label('Subtotal (Rp)')
                            ->content(fn (Get $get) => static::formatRp(
                                (int) ($get('snapshot_nominal_biaya_uji') ?? 0) + (int) ($get('admin_fee') ?? 0)
                            ))
                            ->columnSpan(4)
                            ->dehydrated(false),
                    ]),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('snapshot_nomor_uji')
            ->columns([
                Tables\Columns\TextColumn::make('id')
                    ->label('No.')
                    ->rowIndex(),

                Tables\Columns\TextColumn::make('snapshot_nomor_uji')
                    ->label('Nomor Uji')
                    ->searchable(),

                Tables\Columns\TextColumn::make('snapshot_masa_berlaku')
                    ->label('Masa Berlaku')
                    ->state(function (PengajuanKirItem $record) {
                        $d = $record->snapshot_masa_berlaku;
                        if (! $d) return '-';
                        try {
                            return Carbon::parse($d)->format('d M Y');
                        } catch (\Throwable $e) {
                            return (string) $d;
                        }
                    })
                    ->badge()
                    ->color(function (PengajuanKirItem $record) {
                        if (! $record->snapshot_masa_berlaku) return 'gray';
                        return Carbon::parse($record->snapshot_masa_berlaku)->isPast() ? 'danger' : 'success';
                    })
                    ->description(function (PengajuanKirItem $record) {
                        if (! $record->snapshot_masa_berlaku) return null;
                        return Carbon::parse($record->snapshot_masa_berlaku)->diffForHumans();
                    })
                    ->sortable(),

                Tables\Columns\TextColumn::make('snapshot_nominal_biaya_uji')
                    ->label('Biaya Uji')
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
                        ->visible(fn () => $this->canCreate())
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
        // Broadcast event untuk didengarkan oleh EditPengajuanKir (parent page)
        $this->dispatch('pengajuan-kir-items-updated');
    }

    protected static function formatRp(?int $value): string
    {
        $value = (int) ($value ?? 0);
        return 'Rp ' . number_format($value, 0, ',', '.');
    }
}