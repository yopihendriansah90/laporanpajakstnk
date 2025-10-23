<?php

namespace App\Filament\Resources\KirResource\Pages;

use App\Filament\Resources\KirResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;

class CreateKir extends CreateRecord
{
    protected static string $resource = KirResource::class;
    // setelah berhasil create kembalikan ke index
    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
    
}
