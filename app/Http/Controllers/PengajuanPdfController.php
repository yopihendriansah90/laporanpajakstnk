<?php

namespace App\Http\Controllers;

use App\Models\Pengajuan;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class PengajuanPdfController extends Controller
{
    public function show(Request $request, Pengajuan $pengajuan)
    {
        $pengajuan->load(['items.stnk', 'creator', 'signatories']);

        // Gunakan input export bila ada, fallback ke nilai yang tersimpan di pengajuans
        $divDeptCc = $request->input('div_dept_cc', $pengajuan->div_dept_cc);
        $keperluan = $request->input('keperluan', $pengajuan->keperluan);

        // Ambil penandatangan dari request (jika diisi), atau fallback dari relasi yang tersimpan
        $signInput = $request->input('signatories', null);
        if (is_array($signInput)) {
            if ($this->isArrayOfAssoc($signInput)) {
                $signatories = array_values(array_filter(array_map(fn ($row) => trim((string) ($row['name'] ?? '')), $signInput)));
            } else {
                $signatories = array_values(array_filter(array_map('strval', $signInput)));
            }
        } else {
            $signatories = $pengajuan->signatories->pluck('name')->all();
        }

        // Persist nilai header dan penandatangan ke database agar bisa dipakai di kemudian hari
        $pengajuan->div_dept_cc = $divDeptCc;
        $pengajuan->keperluan = $keperluan;
        $pengajuan->save();

        if (is_array($signInput)) {
            // Sinkronisasi penandatangan: hapus lama dan buat ulang sesuai urutan input
            $pengajuan->signatories()->delete();
            foreach ($signatories as $index => $name) {
                $name = trim((string) $name);
                if ($name === '') {
                    continue;
                }
                $pengajuan->signatories()->create([
                    'name' => $name,
                    'order' => $index + 1,
                ]);
            }
            // Muat ulang relasi agar konsisten untuk render
            $pengajuan->load('signatories');
        }

        $data = [
            'pengajuan' => $pengajuan,
            'items' => $pengajuan->items,
            'div_dept_cc' => $divDeptCc,
            'keperluan' => $keperluan,
            'signatories' => $signatories,
        ];

        // $pdf = Pdf::loadView('pengajuan.pdf', $data)->setPaper('a4', 'portrait');
        $pdf = Pdf::loadView('pengajuan.pdf', $data)->setPaper('a4', 'landscape');


        $content = $pdf->output();

        $filename = sprintf('%s.pdf', $pengajuan->nomor ?? ('pengajuan-' . $pengajuan->getKey()));
        $path = 'pengajuan-pdf/' . $filename;

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