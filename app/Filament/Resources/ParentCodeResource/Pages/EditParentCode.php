<?php

namespace App\Filament\Resources\ParentCodeResource\Pages;

use App\Filament\Resources\ParentCodeResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditParentCode extends EditRecord
{
    protected static string $resource = ParentCodeResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
