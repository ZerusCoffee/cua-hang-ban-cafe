<?php

namespace App\Filament\Resources\Ingredients\Pages;

use App\Filament\Resources\Ingredients\IngredientResource;
use App\Models\Ingredient;
use Filament\Actions\DeleteAction;
use Filament\Actions\ForceDeleteAction;
use Filament\Actions\RestoreAction;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Database\Eloquent\Model;

class EditIngredient extends EditRecord
{
    protected static string $resource = IngredientResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make()
                ->action(fn($record) => $record->safeDelete())
                ->requiresConfirmation(),
            ForceDeleteAction::make(),
            RestoreAction::make(),
        ];
    }
     protected function resolveRecord(int | string $key): Model
    {
        return Ingredient::withTrashed()->findOrFail($key);
    }

}
