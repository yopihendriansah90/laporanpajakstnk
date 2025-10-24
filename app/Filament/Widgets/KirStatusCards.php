<?php

namespace App\Filament\Widgets;

use App\Models\PengajuanKir;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Card;

class KirStatusCards extends BaseWidget
{
    protected ?string $heading = 'Status Pengajuan KIR';

    // Pastikan widget ini mengambil lebar penuh satu baris dashboard
    protected int|string|array $columnSpan = 'full';

    // Atur grid kartu di dalam widget agar 4 kolom pada layar md ke atas
    protected function getColumns(): int
    {
        return 4;
    }

    protected function getCards(): array
    {
        $draft = PengajuanKir::query()->where('status', 'draft')->count();
        $diajukan = PengajuanKir::query()->where('status', 'diajukan')->count();
        $disetujui = PengajuanKir::query()->where('status', 'disetujui')->count();
        $dibayar = PengajuanKir::query()->where('status', 'dibayar')->count();

        return [
            Card::make('Draft', number_format($draft))
                ->description('Pengajuan KIR status draft')
                ->icon('heroicon-o-document')
                ->color('gray'),

            Card::make('Menunggu Approve', number_format($diajukan))
                ->description('Pengajuan KIR status diajukan')
                ->icon('heroicon-o-clock')
                ->color('warning'),

            Card::make('Approve', number_format($disetujui))
                ->description('Pengajuan KIR disetujui')
                ->icon('heroicon-o-check-badge')
                ->color('success'),

            Card::make('Bayar', number_format($dibayar))
                ->description('Pengajuan KIR dibayar')
                ->icon('heroicon-o-banknotes')
                ->color('success'),
        ];
    }
}