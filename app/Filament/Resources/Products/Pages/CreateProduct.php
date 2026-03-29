<?php

namespace App\Filament\Resources\Products\Pages;

use App\Filament\Resources\Products\ProductResource;
use Filament\Resources\Pages\CreateRecord;

class CreateProduct extends CreateRecord
{
    protected static string $resource = ProductResource::class;

    protected float $profitRateInput = 30;

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        // Lưu lại trước khi unset
        $this->profitRateInput = floatval($data['profit_rate_input'] ?? 30);

        unset(
            $data['profit_rate_input'],
            $data['total_cost_final_display'],
            $data['suggested_price_display'],
            $data['profit_calculation_display'],
            $data['actual_profit_rate'],
            $data['calculated_suggested_price'],
        );

        return $data;
    }

    protected function afterCreate(): void
    {
        $totalCost = 0;
        foreach ($this->record->recipeDetails()->with('ingredient')->get() as $detail) {
            if ($detail->ingredient) {
                $totalCost += floatval($detail->ingredient->cost_price) * floatval($detail->amount);
            }
        }
        $totalCost = round($totalCost, 2);

        if ($totalCost > 0) {
            $suggestedPrice = round($totalCost * (1 + $this->profitRateInput / 100), 2);
            $actualProfitRate = round(($suggestedPrice - $totalCost) / $totalCost * 100, 2);
        } else {
            $suggestedPrice = 0;
            $actualProfitRate = 0;
        }

        $this->record->update([
            'recommended_price' => $suggestedPrice,
            'profit_rate'       => $this->profitRateInput,
        ]);
    }
}
