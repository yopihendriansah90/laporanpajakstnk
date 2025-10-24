<?php

namespace App\Http\Controllers;

use App\Models\PengajuanKir;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class PengajuanKirPdfController extends Controller
{
    public function show(Request $request, PengajuanKir $pengajuanKir)
    {
        $pengajuanKir->load(['items.kir.stnk', 'creator', 'signatories']);

        // Gunakan input export bila ada, fallback ke nilai yang tersimpan di pengajuan_kirs
        $divDeptCc = $request->input('div_dept_cc', $pengajuanKir->div_dept_cc);
        $keperluan = $request->input('keperluan', $pengajuanKir->keperluan);

        // Ambil penandatangan dari request (jika diisi), atau fallback dari relasi yang tersimpan
        $signInput = $request->input('signatories', null);
        if (is_array($signInput)) {
            if ($this->isArrayOfAssoc($signInput)) {
                $signatories = array_values(array_filter(array_map(fn ($row) => trim((string) ($row['name'] ?? '')), $signInput)));
            } else {
                $signatories = array_values(array_filter(array_map('strval', $signInput)));
            }
        } else {
            $signatories = $pengajuanKir->signatories->pluck('name')->all();
        }

        // Persist nilai header dan penandatangan ke database agar bisa dipakai di kemudian hari
        $pengajuanKir->div_dept_cc = $divDeptCc;
        $pengajuanKir->keperluan = $keperluan;
        $pengajuanKir->save();

        if (is_array($signInput)) {
            // Sinkronisasi penandatangan: hapus lama dan buat ulang sesuai urutan input
            $pengajuanKir->signatories()->delete();
            foreach ($signatories as $index => $name) {
                $name = trim((string) $name);
                if ($name === '') {
                    continue;
                }
                $pengajuanKir->signatories()->create([
                    'name' => $name,
                    'order' => $index + 1,
                ]);
            }
            // Muat ulang relasi agar konsisten untuk render
            $pengajuanKir->load('signatories');
        }

        $data = [
            'pengajuanKir' => $pengajuanKir,
            'items' => $pengajuanKir->items,
            'div_dept_cc' => $divDeptCc,
            'keperluan' => $keperluan,
            'signatories' => $signatories,
        ];

        $pdf = Pdf::loadView('pengajuan_kir.pdf', $data)->setPaper('a4', 'landscape');

        $content = $pdf->output();

        $filename = sprintf('%s.pdf', $pengajuanKir->nomor ?? ('pengajuan-kir-' . $pengajuanKir->getKey()));
        $path = 'pengajuan-kir-pdf/' . $filename;

        try {
            Storage::disk('public')->put($path, $content);
        } catch (\Throwable $e) {
            // Ignore storage error, continue with download response
        }

        return response($content, 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
        ]);
    }

    private function isArrayOfAssoc(array $arr): bool
    {
        foreach ($arr as $v) {
            if (! is_array($v)) {
                return false;
            }
            if (array_values($v) === $v) {
                return false;
            }
        }

        return $arr !== [];
    }
}