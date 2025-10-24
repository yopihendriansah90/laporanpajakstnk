<?php

namespace App\Filament\Resources\PengajuanKirResource\Pages;

use App\Filament\Resources\PengajuanKirResource;
use Filament\Resources\Pages\CreateRecord;

class CreatePengajuanKir extends CreateRecord
{
    protected static string $resource = PengajuanKirResource::class;

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}