<?php

namespace App\Filament\Resources\PengajuanKirResource\Pages;

use App\Filament\Resources\PengajuanKirResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListPengajuanKirs extends ListRecords
{
    protected static string $resource = PengajuanKirResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}