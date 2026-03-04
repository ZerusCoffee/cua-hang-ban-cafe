<?php

namespace App\Filament\Resources\Products\Pages;

use App\Filament\Resources\Products\ProductResource;
use App\Models\Ingredient;
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
                'recipeDetails' => $recipe->recipeDetails->map(fn($d) => [
                    'ingredient_id' => $d->ingredient_id,
                    'amount' => $d->amount,
                ])->toArray(),
            ];
        }

        // Set profit_rate_input từ product.profit_rate
        $data['profit_rate_input'] = $this->record->profit_rate ?? 30;

        return $data;
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        // Tính toán tổng cost
        $totalCost = $this->calculateTotalCostFromData($data);

        // Lấy profit rate từ input
        $profitRateInput = floatval($data['profit_rate_input'] ?? 30);

        // Tính giá đề xuất và profit rate thực tế
        if ($totalCost > 0) {
            $suggestedPrice = (int) ceil(($totalCost * (1 + $profitRateInput / 100)) / 1000) * 1000;
            $actualProfitRate = round(($suggestedPrice - $totalCost) / $totalCost * 100, 2);
        } else {
            $suggestedPrice = 0;
            $actualProfitRate = 0;
        }

        // Gán vào product để lưu
        $data['recommended_price'] = $suggestedPrice;
        $data['profit_rate'] = $actualProfitRate; // Lưu trực tiếp vào product

        return $data;
    }

    protected function afterSave(): void
    {
        $data = $this->data;

        if (isset($data['recipe'])) {
            DB::transaction(function () use ($data) {
                // Cập nhật hoặc tạo mới recipe
                $recipe = $this->record->recipe()->updateOrCreate(
                    ['product_id' => $this->record->id],
                    ['name' => $data['recipe']['name']] // Không cần lưu profit_rate ở đây nữa
                );

                // Xóa và tạo lại recipe details
                $recipe->recipeDetails()->delete();

                foreach ($data['recipe']['recipeDetails'] ?? [] as $detail) {
                    RecipeDetail::create([
                        'recipe_id' => $recipe->id,
                        'ingredient_id' => $detail['ingredient_id'],
                        'amount' => $detail['amount'],
                    ]);
                }
            });
        }
    }

    private function calculateTotalCostFromData(array $data): float
    {
        $recipeDetails = $data['recipe']['recipeDetails'] ?? [];
        $total = 0;
        foreach ($recipeDetails as $detail) {
            if (!empty($detail['ingredient_id']) && isset($detail['amount'])) {
                $ingredient = Ingredient::find($detail['ingredient_id']);
                if ($ingredient) {
                    $total += floatval($ingredient->cost_price) * floatval($detail['amount']);
                }
            }
        }
        return round($total, 2);
    }
}
