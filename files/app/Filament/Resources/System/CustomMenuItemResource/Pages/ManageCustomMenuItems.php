<?php

namespace App\Filament\Resources\System\CustomMenuItemResource\Pages;

use App\Filament\PageListSingle;
use App\Filament\Resources\System\CustomMenuItemResource;
use Filament\Actions\CreateAction;

class ManageCustomMenuItems extends PageListSingle
{
    protected static string $resource = CustomMenuItemResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
