<?php

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/pengajuan/{pengajuan}/pdf', [\App\Http\Controllers\PengajuanPdfController::class, 'show'])
    ->name('pengajuan.pdf');
