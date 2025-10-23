<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <title>Pengajuan {{ $pengajuan->nomor }}</title>
    <style>
        @page { margin: 24mm 18mm 20mm 18mm; }
        * { box-sizing: border-box; }
        body { font-family: DejaVu Sans, sans-serif; font-size: 12px; color: #111; }
        h1, h2, h3 { margin: 0; padding: 0; }
        .header { text-align: center; margin-bottom: 12px; }
        .title { font-size: 16px; font-weight: 700; }
        .subtitle { font-size: 14px; font-weight: 700; margin-top: 4px; }
        .meta { width: 100%; border-collapse: collapse; margin-top: 10px; }
        .meta td { padding: 4px 6px; vertical-align: top; }
        .meta td.label { width: 120px; color: #555; }
        .items { width: 100%; border-collapse: collapse; margin-top: 12px; }
        .items th, .items td { border: 1px solid #333; padding: 6px 8px; }
        .items th { background: #f2f2f2; text-transform: uppercase; font-size: 11px; }
        .items td.num { text-align: center; width: 28px; }
        .right { text-align: right; }
        .total { margin-top: 10px; width: 100%; }
        .total .label { font-weight: 700; }
        .signatures { margin-top: 24px; width: 100%; }
        .sign-row { display: table; width: 100%; table-layout: fixed; }
        .sign-col { display: table-cell; text-align: center; padding: 0 6px; }
        .sign-space { height: 48px; }
        .sign-name { margin-top: 4px; border-top: 1px solid #333; padding-top: 4px; font-size: 11px; }
    </style>
</head>
<body>
@php
    use Illuminate\Support\Carbon;
    $fmtRp = function (?int $v): string {
        $v = (int)($v ?? 0);
        return 'Rp ' . number_format($v, 0, ',', '.');
    };
    $fmtDate = function ($d): string {
        if (!$d) return '-';
        try {
            return Carbon::parse($d)->locale('id')->translatedFormat('d F Y');
        } catch (\Throwable $e) {
            return Carbon::parse($d)->format('d M Y');
        }
    };
    $headerTanggal = $pengajuan->submitted_at ?? $pengajuan->created_at;
    $signs = array_values(($signatories ?? []));
    for ($i = count($signs); $i < 5; $i++) { $signs[] = ''; }
@endphp

<div class="header">
    <div class="title">PENGAJUAN BIAYA ADVANCE</div>
    <div class="subtitle">RINCIAN PERPANJANG STNK</div>
</div>

<table class="meta">
    <tr>
        <td class="label">No Surat</td>
        <td>: {{ $pengajuan->nomor }}</td>
        <td class="label">Tanggal</td>
        <td>: {{ $fmtDate($headerTanggal) }}</td>
    </tr>
    <tr>
        <td class="label">Div/ Dept/ CC</td>
        <td>: {{ $div_dept_cc ?? '-' }}</td>
        <td class="label">Keperluan</td>
        <td>: {{ $keperluan ?? '-' }}</td>
    </tr>
</table>

<table class="items">
    <thead>
        <tr>
            <th style="width:30px;">No</th>
            <th>Nomor Polisi</th>
            <th>Jatuh Tempo 5 Tahun</th>
            <th>Jatuh Tempo 1 Tahun</th>
            <th class="right" style="width:120px;">Sub Total</th>
            {{-- <th>Keterangan</th> --}}
        </tr>
    </thead>
    <tbody>
    @foreach ($items as $idx => $item)
        @php
            $stnk = $item->stnk ?? null;
            $tgl5 = $stnk?->masa_berlaku_5;
            $tgl1 = $stnk?->masa_berlaku_1;
            $ket = trim(($stnk->merk_kendaraan ?? '') . ' ' . (($stnk->tipe_kendaraan ?? '') ?: ($stnk->model_kendaraan ?? '')));
            $subtotal = (int)($item->subtotal ?? ((int)$item->snapshot_nominal_pokok_pajak + (int)$item->admin_fee));
        @endphp
        <tr>
            <td class="num">{{ $idx + 1 }}</td>
            <td>{{ $item->snapshot_nomor_polisi ?: ($stnk->nomor_polisi ?? '-') }}</td>
            <td>{{ $fmtDate($tgl5) }}</td>
            <td>{{ $fmtDate($tgl1) }}</td>
            <td class="right">{{ $fmtRp($subtotal) }}</td>
            {{-- <td>{{ $ket ?: '-' }}</td> --}}
        </tr>
    @endforeach
   @if (count($items) === 0)
       <tr>
           <td class="num">-</td>
           <td colspan="5">Tidak ada item.</td>
       </tr>
   @endif
   </tbody>
</table>

<table class="total">
    <tr>
        <td style="text-align:right;">
            <span class="label">TOTAL</span>
            <span style="display:inline-block; min-width: 140px; text-align:right; margin-left: 12px;">
                {{ $fmtRp($pengajuan->grand_total ?? 0) }}
            </span>
        </td>
    </tr>
</table>

<div class="signatures">
    <div class="sign-row">
        @foreach ($signs as $name)
            <div class="sign-col">
                <div class="sign-space"></div>
                <div class="sign-name">({{ $name }})</div>
            </div>
        @endforeach
    </div>
</div>

</body>
</html>