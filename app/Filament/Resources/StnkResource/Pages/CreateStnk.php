<?php

namespace App\Filament\Resources\StnkResource\Pages;

use App\Filament\Resources\StnkResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;

class CreateStnk extends CreateRecord
{
    protected static string $resource = StnkResource::class;
 
    // after create redirect to table index
    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
    
}
