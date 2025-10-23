<?php

namespace App\Filament\Resources\KirResource\Pages;

use App\Filament\Resources\KirResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListKirs extends ListRecords
{
    protected static string $resource = KirResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
