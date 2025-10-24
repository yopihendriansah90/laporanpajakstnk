<?php

namespace App\Filament\Widgets;

use App\Models\Kir;
use App\Models\Pengajuan;
use App\Models\PengajuanItem;
use App\Models\PengajuanKir;
use App\Models\PengajuanKirItem;
use App\Models\Stnk;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Card;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;

class OperationalKpiStats extends BaseWidget
{
    protected ?string $heading = 'Operasional: KPI';

    protected function getCards(): array
    {
        $days = (int) config('dashboard.soon_due_days', 30);
        $feeThreshold = (int) config('dashboard.outlier_admin_fee', 100000);

        $today = Carbon::today();
        $until = Carbon::today()->addDays($days);

        $cacheKey = sprintf(
            'kpi_operational_%s_%s_%s',
            $days,
            $feeThreshold,
            $today->toDateString()
        );

        $data = Cache::remember($cacheKey, 60, function () use ($today, $until, $feeThreshold) {
            // STNK due: hitung masing-masing masa_berlaku_1 dan masa_berlaku_5 sebagai entri terpisah
            $stnkDue1 = Stnk::query()
                ->whereNotNull('masa_berlaku_1')
                ->whereBetween('masa_berlaku_1', [$today, $until])
                ->count();

            $stnkDue5 = Stnk::query()
                ->whereNotNull('masa_berlaku_5')
                ->whereBetween('masa_berlaku_5', [$today, $until])
                ->count();

            // KIR due: berdasarkan masa_berlaku KIR
            $kirDue = Kir::query()
                ->whereNotNull('masa_berlaku')
                ->whereBetween('masa_berlaku', [$today, $until])
                ->count();

            $soonDue = $stnkDue1 + $stnkDue5 + $kirDue;

            // Menunggu approval: pengajuan STNK & pengajuan KIR berstatus diajukan
            $pendingApproval = Pengajuan::query()->where('status', 'diajukan')->count()
                + PengajuanKir::query()->where('status', 'diajukan')->count();

            // Outlier admin fee (gabungan item STNK & KIR)
            $outliers = PengajuanItem::query()->where('admin_fee', '>', $feeThreshold)->count()
                + PengajuanKirItem::query()->where('admin_fee', '>', $feeThreshold)->count();

            return [
                'soonDue' => $soonDue,
                'stnkDue1' => $stnkDue1,
                'stnkDue5' => $stnkDue5,
                'kirDue' => $kirDue,
                'pendingApproval' => $pendingApproval,
                'outliers' => $outliers,
            ];
        });

        $soonDueCard = Card::make(
            'Akan Jatuh Tempo (≤ ' . $days . ' hari)',
            number_format($data['soonDue'])
        )
            ->description('STNK 1 th: ' . number_format($data['stnkDue1']) .
                ' | STNK 5 th: ' . number_format($data['stnkDue5']) .
                ' | KIR: ' . number_format($data['kirDue']))
            ->icon('heroicon-o-clock')
            ->color('warning');

        $pendingCard = Card::make(
            'Menunggu Approval',
            number_format($data['pendingApproval'])
        )
            ->description('Pengajuan STNK + Pengajuan KIR')
            ->icon('heroicon-o-inbox-arrow-down')
            ->color('info');

        $outlierCard = Card::make(
            'Outlier Admin Fee (> Rp ' . number_format($feeThreshold, 0, ',', '.') . ')',
            number_format($data['outliers'])
        )
            ->description('Item STNK + KIR dengan admin fee tinggi')
            ->icon('heroicon-o-exclamation-triangle')
            ->color('danger');

        return [
            $soonDueCard,
            $pendingCard,
            $outlierCard,
        ];
    }
}