<?php

namespace App\Filament\Resources\Products\Pages;

use App\Filament\Resources\Products\ProductResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditProduct extends EditRecord
{
    protected static string $resource = ProductResource::class;

    protected float $profitRateInput = 30;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

    protected function mutateFormDataBeforeFill(array $data): array
    {
        $data['profit_rate_input'] = $this->record->profit_rate ?? 30;

        return $data;
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        // Lưu lại trước khi unset
        $this->profitRateInput = floatval($data['profit_rate_input'] ?? 30);

        unset(
            $data['profit_rate_input'],
            $data['total_cost_final_display'],
            $data['suggested_price_display'],
            $data['profit_calculation_display'],
        );

        return $data;
    }

    protected function afterSave(): void
    {
        $totalCost = 0;
        foreach ($this->record->recipeDetails()->with('ingredient')->get() as $detail) {
            if ($detail->ingredient) {
                $totalCost += floatval($detail->ingredient->cost_price) * floatval($detail->amount);
            }
        }
        $totalCost = round($totalCost, 2);

        if ($totalCost > 0) {
            $suggestedPrice = (int) ceil(($totalCost * (1 + $this->profitRateInput / 100)) / 1000) * 1000;
            $actualProfitRate = round(($suggestedPrice - $totalCost) / $totalCost * 100, 2);
        } else {
            $suggestedPrice = 0;
            $actualProfitRate = 0;
        }

        $this->record->update([
            'recommended_price' => $suggestedPrice,
            'profit_rate'       => $actualProfitRate,
        ]);
    }
}
