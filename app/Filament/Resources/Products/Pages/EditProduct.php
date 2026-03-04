<?php

namespace App\Filament\Resources\Products\Pages;

use App\Filament\Resources\Products\ProductResource;
use App\Models\RecipeDetail;
use Filament\Actions\DeleteAction;
use Filament\Actions\ForceDeleteAction;
use Filament\Actions\RestoreAction;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Support\Facades\DB;

class EditProduct extends EditRecord
{
    protected static string $resource = ProductResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
            ForceDeleteAction::make(),
            RestoreAction::make(),
        ];
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

    protected function mutateFormDataBeforeFill(array $data): array
    {
        $recipe = $this->record->recipe;

        if ($recipe) {
            $data['recipe'] = [
                'name' => $recipe->name,
                'recipeDetails' => $recipe->recipeDetails->map(fn ($d) => [
                    'ingredient_id' => $d->ingredient_id,
                    'amount' => $d->amount,
                ])->toArray(),
            ];
        }

        return $data;
    }

    protected function afterSave(): void
    {
        $data = $this->data;

        if (isset($data['recipe'])) {
            DB::transaction(function () use ($data) {
                $recipe = $this->record->recipe()->updateOrCreate(
                    ['product_id' => $this->record->id],
                    ['name' => $data['recipe']['name']],
                );

                $recipe->recipeDetails()->delete();

                foreach ($data['recipe']['recipeDetails'] ?? [] as $detail) {
                    RecipeDetail::create([
                        'recipe_id'     => $recipe->id,
                        'ingredient_id' => $detail['ingredient_id'],
                        'amount'        => $detail['amount'],
                    ]);
                }
            });
        }
    }
}
