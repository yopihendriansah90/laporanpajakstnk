<?php

namespace App\Filament\Resources\StnkResource\Pages;

use App\Filament\Resources\StnkResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditStnk extends EditRecord
{
    protected static string $resource = StnkResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
            Actions\ForceDeleteAction::make(),
            Actions\RestoreAction::make(),
        ];
    }
}
