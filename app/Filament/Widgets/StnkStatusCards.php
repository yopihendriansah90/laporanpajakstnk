<?php

namespace App\Filament\Widgets;

use App\Models\Pengajuan;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Card;

class StnkStatusCards extends BaseWidget
{
    protected ?string $heading = 'Status Pengajuan STNK';

    // Pastikan widget ini mengambil lebar penuh satu baris dashboard
    protected int|string|array $columnSpan = 'full';

    // Atur grid kartu di dalam widget agar 4 kolom pada layar md ke atas
    protected function getColumns(): int
    {
        return 4;
    }

    protected function getCards(): array
    {
        $draft = Pengajuan::query()->where('status', 'draft')->count();
        $diajukan = Pengajuan::query()->where('status', 'diajukan')->count();
        $disetujui = Pengajuan::query()->where('status', 'disetujui')->count();
        $dibayar = Pengajuan::query()->where('status', 'dibayar')->count();

        return [
            Card::make('Draft', number_format($draft))
                ->description('Pengajuan STNK status draft')
                ->icon('heroicon-o-document')
                ->color('gray'),

            Card::make('Menunggu Approve', number_format($diajukan))
                ->description('Pengajuan STNK status diajukan')
                ->icon('heroicon-o-clock')
                ->color('warning'),

            Card::make('Approve', number_format($disetujui))
                ->description('Pengajuan STNK disetujui')
                ->icon('heroicon-o-check-badge')
                ->color('success'),

            Card::make('Bayar', number_format($dibayar))
                ->description('Pengajuan STNK dibayar')
                ->icon('heroicon-o-banknotes')
                ->color('success'),
        ];
    }
}