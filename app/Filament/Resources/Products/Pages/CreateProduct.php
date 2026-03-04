<?php

namespace App\Filament\Resources\Products\Pages;

use App\Filament\Resources\Products\ProductResource;
use App\Models\Recipe;
use App\Models\RecipeDetail;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Facades\DB;

class CreateProduct extends CreateRecord
{
    protected static string $resource = ProductResource::class;

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

    protected function afterCreate(): void
    {
        $data = $this->data;

        if (isset($data['recipe'])) {
            DB::transaction(function () use ($data) {
                $recipe = Recipe::create([
                    'product_id' => $this->record->id,
                    'name'       => $data['recipe']['name'],
                ]);

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
