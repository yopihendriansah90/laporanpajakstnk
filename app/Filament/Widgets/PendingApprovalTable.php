<?php

namespace App\Filament\Widgets;

use App\Filament\Resources\PengajuanKirResource;
use App\Filament\Resources\PengajuanResource;
use App\Models\Pengajuan;
use App\Models\PengajuanKir;
use Filament\Widgets\Widget;
use Illuminate\Support\Carbon;

class PendingApprovalTable extends Widget
{
    protected static ?string $heading = 'Menunggu Approval';

    protected static string $view = 'filament.widgets.pending-approval-table';

    protected int|string|array $columnSpan = 'full';

    protected function getViewData(): array
    {
        $limit = (int) config('dashboard.table_limit', 20);

        // Pengajuan STNK yang diajukan
        $stnk = Pengajuan::query()
            ->where('status', 'diajukan')
            ->withCount('items')
            ->select(['id', 'nomor', 'submitted_at', 'grand_total'])
            ->get()
            ->map(function (Pengajuan $p) {
                return [
                    'jenis' => 'Pengajuan STNK',
                    'nomor' => (string) $p->nomor,
                    'submitted_at' => $p->submitted_at instanceof \DateTimeInterface
                        ? Carbon::parse($p->submitted_at)
                        : Carbon::parse($p->submitted_at),
                    'grand_total' => (int) ($p->grand_total ?? 0),
                    'items_count' => (int) ($p->items_count ?? 0),
                    'url' => PengajuanResource::getUrl('edit', ['record' => $p->id]),
                ];
            });

        // Pengajuan KIR yang diajukan
        $kir = PengajuanKir::query()
            ->where('status', 'diajukan')
            ->withCount('items')
            ->select(['id', 'nomor', 'submitted_at', 'grand_total'])
            ->get()
            ->map(function (PengajuanKir $k) {
                return [
                    'jenis' => 'Pengajuan KIR',
                    'nomor' => (string) $k->nomor,
                    'submitted_at' => $k->submitted_at instanceof \DateTimeInterface
                        ? Carbon::parse($k->submitted_at)
                        : Carbon::parse($k->submitted_at),
                    'grand_total' => (int) ($k->grand_total ?? 0),
                    'items_count' => (int) ($k->items_count ?? 0),
                    'url' => PengajuanKirResource::getUrl('edit', ['record' => $k->id]),
                ];
            });

        $rows = collect()
            ->merge($stnk)
            ->merge($kir)
            ->sortByDesc('submitted_at')
            ->take($limit)
            ->values()
            ->all();

        return [
            'rows' => $rows,
            'limit' => $limit,
        ];
    }
}