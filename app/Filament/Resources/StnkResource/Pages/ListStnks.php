<?php

namespace App\Filament\Resources\StnkResource\Pages;

use App\Filament\Resources\StnkResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListStnks extends ListRecords
{
    protected static string $resource = StnkResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
