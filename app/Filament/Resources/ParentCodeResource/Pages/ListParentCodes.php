<?php

namespace App\Filament\Resources\ParentCodeResource\Pages;

use App\Filament\Resources\ParentCodeResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListParentCodes extends ListRecords
{
    protected static string $resource = ParentCodeResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()->label('新增家長註冊碼'),
        ];
    }
}
