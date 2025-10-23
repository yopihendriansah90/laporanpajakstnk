<?php

namespace App\Filament\Resources\KirResource\Pages;

use App\Filament\Resources\KirResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditKir extends EditRecord
{
    protected static string $resource = KirResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
            Actions\ForceDeleteAction::make(),
            Actions\RestoreAction::make(),
        ];
    }
}
