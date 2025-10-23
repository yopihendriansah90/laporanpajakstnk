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
        $pengajuan->load(['items.stnk', 'creator']);

        $divDeptCc = $request->input('div_dept_cc');
        $keperluan = $request->input('keperluan');

        $signInput = $request->input('signatories', []);
        if (is_array($signInput)) {
            if ($this->isArrayOfAssoc($signInput)) {
                $signatories = array_values(array_filter(array_map(fn ($row) => trim((string) ($row['name'] ?? '')), $signInput)));
            } else {
                $signatories = array_values(array_filter(array_map('strval', $signInput)));
            }
        } else {
            $signatories = [];
        }

        $data = [
            'pengajuan' => $pengajuan,
            'items' => $pengajuan->items,
            'div_dept_cc' => $divDeptCc,
            'keperluan' => $keperluan,
            'signatories' => $signatories,
        ];

        $pdf = Pdf::loadView('pengajuan.pdf', $data)->setPaper('a4', 'portrait');

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