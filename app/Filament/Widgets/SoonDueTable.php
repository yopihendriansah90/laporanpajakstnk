<?php

namespace App\Filament\Widgets;

use App\Filament\Resources\KirResource;
use App\Filament\Resources\StnkResource;
use App\Models\Kir;
use App\Models\Stnk;
use Filament\Widgets\Widget;
use Illuminate\Support\Carbon;

class SoonDueTable extends Widget
{
    protected static ?string $heading = 'Akan Jatuh Tempo (STNK & KIR)';

    protected static string $view = 'filament.widgets.soon-due-table';

    protected int|string|array $columnSpan = 'full';

    protected function getViewData(): array
    {
        $days = (int) config('dashboard.soon_due_days', 30);
        $limit = (int) config('dashboard.table_limit', 20);

        $today = Carbon::today();
        $until = Carbon::today()->addDays($days);

        // STNK due (1 tahun)
        $stnk1 = Stnk::query()
            ->whereNotNull('masa_berlaku_1')
            ->whereBetween('masa_berlaku_1', [$today, $until])
            ->select(['id', 'nomor_polisi', 'masa_berlaku_1'])
            ->get()
            ->map(function ($s) use ($today) {
                $due = Carbon::parse($s->masa_berlaku_1);
                return [
                    'jenis' => 'STNK 1 Tahun',
                    'identitas' => (string) $s->nomor_polisi,
                    'due_date' => $due,
                    'days_left' => $due->diffInDays($today),
                    'url' => StnkResource::getUrl('edit', ['record' => $s->id]),
                ];
            });

        // STNK due (5 tahun)
        $stnk5 = Stnk::query()
            ->whereNotNull('masa_berlaku_5')
            ->whereBetween('masa_berlaku_5', [$today, $until])
            ->select(['id', 'nomor_polisi', 'masa_berlaku_5'])
            ->get()
            ->map(function ($s) use ($today) {
                $due = Carbon::parse($s->masa_berlaku_5);
                return [
                    'jenis' => 'STNK 5 Tahun',
                    'identitas' => (string) $s->nomor_polisi,
                    'due_date' => $due,
                    'days_left' => $due->diffInDays($today),
                    'url' => StnkResource::getUrl('edit', ['record' => $s->id]),
                ];
            });

        // KIR due
        $kirDue = Kir::query()
            ->whereNotNull('masa_berlaku')
            ->whereBetween('masa_berlaku', [$today, $until])
            ->select(['id', 'nomor_uji_kendaraan', 'masa_berlaku'])
            ->get()
            ->map(function ($k) use ($today) {
                $due = Carbon::parse($k->masa_berlaku);
                return [
                    'jenis' => 'KIR',
                    'identitas' => (string) $k->nomor_uji_kendaraan,
                    'due_date' => $due,
                    'days_left' => $due->diffInDays($today),
                    'url' => KirResource::getUrl('edit', ['record' => $k->id]),
                ];
            });

        $rows = collect()
            ->merge($stnk1)
            ->merge($stnk5)
            ->merge($kirDue)
            ->sortBy('due_date')
            ->take($limit)
            ->values()
            ->all();

        return [
            'rows' => $rows,
            'days' => $days,
            'limit' => $limit,
        ];
    }
}