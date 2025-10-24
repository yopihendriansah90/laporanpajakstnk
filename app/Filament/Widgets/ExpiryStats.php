<?php

namespace App\Filament\Widgets;

use App\Models\Kir;
use App\Models\Stnk;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Card;
use Illuminate\Support\Carbon;

class ExpiryStats extends BaseWidget
{
    protected ?string $heading = 'Kadaluarsa ≤ 30 Hari';

    // Ambil lebar penuh satu baris dan atur grid 3 kolom untuk kartu expiry
    protected int|string|array $columnSpan = 'full';

    protected function getColumns(): int
    {
        return 3;
    }

    protected function getCards(): array
    {
        $days = (int) config('dashboard.soon_due_days', 30);
        $today = Carbon::today();
        $until = Carbon::today()->addDays($days);

        $stnk1Count = Stnk::query()
            ->whereNotNull('masa_berlaku_1')
            ->whereBetween('masa_berlaku_1', [$today, $until])
            ->count();

        $stnk5Count = Stnk::query()
            ->whereNotNull('masa_berlaku_5')
            ->whereBetween('masa_berlaku_5', [$today, $until])
            ->count();

        $kirCount = Kir::query()
            ->whereNotNull('masa_berlaku')
            ->whereBetween('masa_berlaku', [$today, $until])
            ->count();

        $stnk1Card = Card::make(
            'STNK 1 Tahun',
            number_format($stnk1Count)
        )
            ->description('Jatuh tempo ≤ ' . $days . ' hari')
            ->icon('heroicon-o-calendar')
            ->color('warning');

        $stnk5Card = Card::make(
            'STNK 5 Tahun',
            number_format($stnk5Count)
        )
            ->description('Jatuh tempo ≤ ' . $days . ' hari')
            ->icon('heroicon-o-calendar-days')
            ->color('warning');

        $kirCard = Card::make(
            'KIR',
            number_format($kirCount)
        )
            ->description('Jatuh tempo ≤ ' . $days . ' hari')
            ->icon('heroicon-o-truck')
            ->color('warning');

        return [
            $stnk1Card,
            $stnk5Card,
            $kirCard,
        ];
    }
}