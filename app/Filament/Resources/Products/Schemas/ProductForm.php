<?php

namespace App\Filament\Resources\Products\Schemas;

use App\Models\Ingredient;
use App\Models\Option;
use App\Models\OptionGroup;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Components\Wizard;
use Filament\Schemas\Components\Wizard\Step;
use Filament\Schemas\Schema;
use Illuminate\Support\Str;
use Livewire\Component as Livewire;

class ProductForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Wizard::make([
                    Step::make('Thông tin cơ bản')
                        ->icon('heroicon-o-information-circle')
                        ->schema([
                            Select::make('category_id')
                                ->label('Danh mục')
                                ->relationship('category', 'name')
                                ->searchable()
                                ->preload()
                                ->required()
                                ->createOptionForm([
                                    TextInput::make('name')
                                        ->label('Tên danh mục')
                                        ->required()
                                        ->maxLength(255),

                                    TextInput::make('slug')
                                        ->label('Slug')
                                        ->unique('categories', 'slug')
                                        ->maxLength(255)
                                        ->helperText('Tự động tạo từ tên danh mục'),
                                ]),

                            TextInput::make('name')
                                ->label('Tên sản phẩm')
                                ->required()
                                ->maxLength(255)
                                ->live()
                                ->afterStateUpdated(fn($state, callable $set) => $set('slug', Str::slug($state))),

                            TextInput::make('slug')
                                ->label('Slug')
                                ->unique(ignoreRecord: true)
                                ->maxLength(255)
                                ->helperText('Tự động tạo từ tên sản phẩm'),

                            TextInput::make('sku')
                                ->label('SKU')
                                ->placeholder('Tự động tạo sau khi lưu (SP-001)')
                                ->disabled()
                                ->dehydrated(false)
                                ->visibleOn('edit'),

                            Toggle::make('is_active')
                                ->label('Đang bán')
                                ->default(true),

                            Toggle::make('is_featured')
                                ->label('Sản phẩm nổi bật')
                                ->default(false),
                        ])
                        ->columns(2),

                    Step::make('Mô tả & Hình ảnh')
                        ->icon('heroicon-o-photo')
                        ->schema([
                            Textarea::make('short_description')
                                ->label('Mô tả ngắn')
                                ->rows(3)
                                ->nullable(),

                            Textarea::make('description')
                                ->label('Mô tả chi tiết')
                                ->rows(8)
                                ->nullable()
                                ->columnSpanFull(),

                            Repeater::make('images')
                                ->label('Hình ảnh')
                                ->relationship('images')
                                ->schema([
                                    FileUpload::make('image_path')
                                        ->label('Ảnh')
                                        ->image()
                                        ->required()
                                        ->disk('public')
                                        ->directory('products'),

                                    TextInput::make('alt_text')
                                        ->label('Alt text')
                                        ->nullable(),

                                    Toggle::make('is_primary')
                                        ->label('Ảnh chính')
                                        ->default(false)
                                        ->live()
                                        ->afterStateUpdated(function ($state, Set $set, Get $get, $livewire) {
                                            if ($state) {
                                                $images = $livewire->data['images'] ?? [];
                                                foreach (array_keys($images) as $key) {
                                                    $livewire->data['images'][$key]['is_primary'] = false;
                                                }
                                                $currentPath = $get('image_path');
                                                foreach (array_keys($images) as $key) {
                                                    if ($livewire->data['images'][$key]['image_path'] === $currentPath) {
                                                        $livewire->data['images'][$key]['is_primary'] = true;
                                                        break;
                                                    }
                                                }
                                            }
                                        }),
                                ])
                                ->columns(3)
                                ->addActionLabel('Thêm ảnh')
                                ->collapsible()
                                ->columnSpanFull(),
                        ])
                        ->columns(2),

                    Step::make('Công thức')
                        ->icon('heroicon-o-beaker')
                        ->schema([
                            Repeater::make('recipeDetails')
                                ->label('Nguyên liệu')
                                ->relationship('recipeDetails')
                                ->schema([
                                    Select::make('ingredient_id')
                                        ->label('Nguyên liệu')
                                        ->options(function () {
                                            return Ingredient::query()
                                                ->with('unit')
                                                ->get()
                                                ->mapWithKeys(fn($i) => [
                                                    $i->id => sprintf(
                                                        '%s (%s) - %s%s',
                                                        $i->name,
                                                        $i->unit?->symbol ?? '',
                                                        number_format($i->cost_price),
                                                        $i->unit?->symbol ? 'đ/' . $i->unit->symbol : 'đ'
                                                    )
                                                ]);
                                        })
                                        ->searchable()
                                        ->required()
                                        ->distinct()
                                        ->disableOptionsWhenSelectedInSiblingRepeaterItems()
                                        ->live(onBlur: false)
                                        ->afterStateUpdated(function ($state, Set $set, Get $get, Livewire $livewire) {
                                            $amount = floatval($get('amount') ?? 0);
                                            $itemCost = 0;
                                            if ($state) {
                                                $ingredient = Ingredient::find($state);
                                                $itemCost = $ingredient ? $ingredient->cost_price * $amount : 0;
                                            }
                                            $set('ingredient_cost_display', number_format($itemCost));
                                            self::updateAllDisplays($set, $livewire);
                                        }),

                                    TextInput::make('amount')
                                        ->label('Số lượng')
                                        ->required()
                                        ->numeric()
                                        ->minValue(0)
                                        ->step(0.01)
                                        ->live(onBlur: false)
                                        ->afterStateUpdated(function ($state, Set $set, Get $get, Livewire $livewire) {
                                            $ingredientId = $get('ingredient_id');
                                            $itemCost = 0;
                                            if ($ingredientId && $state) {
                                                $ingredient = Ingredient::find($ingredientId);
                                                $itemCost = $ingredient ? $ingredient->cost_price * floatval($state) : 0;
                                            }
                                            $set('ingredient_cost_display', number_format($itemCost));
                                            self::updateAllDisplays($set, $livewire);
                                        }),

                                    TextInput::make('ingredient_cost_display')
                                        ->label('Thành tiền')
                                        ->disabled()
                                        ->dehydrated(false)
                                        ->suffix('đ')
                                        ->afterStateHydrated(function (TextInput $component, Get $get) {
                                            $ingredientId = $get('ingredient_id');
                                            $amount = floatval($get('amount') ?? 0);
                                            if ($ingredientId) {
                                                $ingredient = Ingredient::find($ingredientId);
                                                if ($ingredient) {
                                                    $component->state(number_format($ingredient->cost_price * $amount));
                                                    return;
                                                }
                                            }
                                            $component->state('0');
                                        }),
                                ])
                                ->columns(3)
                                ->addActionLabel('Thêm nguyên liệu')
                                ->reorderable()
                                ->collapsible()
                                ->defaultItems(1)
                                ->columnSpanFull()
                                ->live()
                                ->afterStateUpdated(function (Set $set, Get $get, Livewire $livewire) {
                                    self::updateAllDisplays($set, $livewire);
                                })
                                ->deleteAction(fn($action) => $action->requiresConfirmation()),
                        ])
                        ->columns(2),

                    Step::make('Giá bán')
                        ->icon('heroicon-o-currency-dollar')
                        ->schema([
                            TextInput::make('total_cost_final_display')
                                ->label('Tổng giá cost nguyên liệu')
                                ->disabled()
                                ->dehydrated(false)
                                ->suffix('đ')
                                ->afterStateHydrated(function (TextInput $component, Get $get) {
                                    $component->state(number_format(self::calculateTotalCostFromGet($get)));
                                }),

                            TextInput::make('profit_rate_input')
                                ->label('Tỷ lệ lợi nhuận (%)')
                                ->required()
                                ->numeric()
                                ->suffix('%')
                                ->minValue(0)
                                ->maxValue(10000)
                                ->default(30)
                                ->live(debounce: 500)
                                ->step(0.01)
                                ->afterStateHydrated(function (TextInput $component, Get $get) {
                                    $savedRate = $get('profit_rate');
                                    if ($savedRate !== null) {
                                        $component->state($savedRate);
                                    }
                                })
                                ->afterStateUpdated(function ($state, Set $set, Livewire $livewire) {
                                    self::updateAllDisplays($set, $livewire);
                                }),

                            TextInput::make('suggested_price_display')
                                ->label('Giá đề xuất (tự động)')
                                ->disabled()
                                ->dehydrated(false)
                                ->suffix('đ')
                                ->afterStateHydrated(function (TextInput $component, Get $get) {
                                    $totalCost = self::calculateTotalCostFromGet($get);
                                    $profitRate = floatval($get('profit_rate_input') ?? $get('profit_rate') ?? 30);
                                    $component->state(number_format(self::calculateSuggestedPrice($totalCost, $profitRate)));
                                }),

                            TextInput::make('profit_calculation_display')
                                ->label('Lợi nhuận dự kiến')
                                ->disabled()
                                ->dehydrated(false)
                                ->columnSpanFull()
                                ->afterStateHydrated(function (TextInput $component, Get $get) {
                                    $totalCost = self::calculateTotalCostFromGet($get);
                                    $profitRate = floatval($get('profit_rate_input') ?? $get('profit_rate') ?? 30);
                                    $suggestedPrice = self::calculateSuggestedPrice($totalCost, $profitRate);
                                    if ($totalCost > 0) {
                                        $profit = $suggestedPrice - $totalCost;
                                        $profitPercent = round(($profit / $totalCost) * 100, 1);
                                        $component->state(number_format($profit) . 'đ (' . $profitPercent . '%)');
                                    } else {
                                        $component->state('0');
                                    }
                                }),
                        ])
                        ->columns(2),

                    Step::make('Tuỳ chọn')
                        ->icon('heroicon-o-adjustments-horizontal')
                        ->schema([
                            Repeater::make('productOptions')
                                ->label('Tuỳ chọn sản phẩm')
                                ->relationship('productOptions')
                                ->schema([
                                    Select::make('option_id')
                                        ->label('Tuỳ chọn')
                                        ->options(function () {
                                            return Option::query()
                                                ->with('group')
                                                ->get()
                                                ->mapWithKeys(fn($o) => [
                                                    $o->id => $o->group->name . ' - ' . $o->value,
                                                ]);
                                        })
                                        ->searchable()
                                        ->required()
                                        ->distinct()
                                        ->disableOptionsWhenSelectedInSiblingRepeaterItems(),

                                    TextInput::make('additional_price')
                                        ->label('Giá thêm')
                                        ->required()
                                        ->numeric()
                                        ->default(0)
                                        ->minValue(0)
                                        ->suffix('đ'),

                                    // Modifiers lồng trong từng product_option → dùng product_option_id
                                    Repeater::make('modifiers')
                                        ->label('Ảnh hưởng nguyên liệu')
                                        ->relationship('modifiers')
                                        ->schema([
                                            Select::make('ingredient_id')
                                                ->label('Nguyên liệu')
                                                ->options(function () {
                                                    return Ingredient::query()
                                                        ->with('unit')
                                                        ->get()
                                                        ->mapWithKeys(fn($i) => [
                                                            $i->id => $i->name . ($i->unit ? ' (' . $i->unit->symbol . ')' : ''),
                                                        ]);
                                                })
                                                ->searchable()
                                                ->required()
                                                ->distinct()
                                                ->disableOptionsWhenSelectedInSiblingRepeaterItems(),

                                            TextInput::make('delta_quantity')
                                                ->label('Thay đổi số lượng')
                                                ->required()
                                                ->numeric()
                                                ->step(0.01)
                                                ->helperText('Âm = bớt, Dương = thêm'),
                                        ])
                                        ->columns(2)
                                        ->addActionLabel('Thêm nguyên liệu bị ảnh hưởng')
                                        ->defaultItems(0)
                                        ->columnSpanFull()
                                        ->deleteAction(fn($action) => $action->requiresConfirmation()),
                                ])
                                ->columns(2)
                                ->addActionLabel('Thêm tuỳ chọn')
                                ->collapsible()
                                ->reorderable()
                                ->defaultItems(0)
                                ->columnSpanFull()
                                ->deleteAction(fn($action) => $action->requiresConfirmation()),
                        ])
                        ->columns(1),
                ])
                    ->columnSpanFull()
                    ->skippable(),
            ]);
    }

    private static function calculateTotalCostFromGet(Get $get): float
    {
        $recipeDetails = $get('recipeDetails') ?? [];
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

    private static function calculateTotalCostFromLivewire(Livewire $livewire): float
    {
        $recipeDetails = $livewire->data['recipeDetails'] ?? [];
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

    private static function calculateSuggestedPrice(float $totalCost, float $profitRate): float
    {
        if ($totalCost <= 0) return 0;
        return round($totalCost * (1 + $profitRate / 100), 2);
    }

    private static function updateAllDisplays(Set $set, Livewire $livewire): void
    {
        $totalCost = self::calculateTotalCostFromLivewire($livewire);
        $profitRateInput = floatval($livewire->data['profit_rate_input'] ?? 30);

        $suggestedPrice = self::calculateSuggestedPrice($totalCost, $profitRateInput);

        $actualProfitRate = 0;
        if ($totalCost > 0 && $suggestedPrice > 0) {
            $actualProfitRate = round(($suggestedPrice - $totalCost) / $totalCost * 100, 2);
        }

        $set('total_cost_final_display', number_format($totalCost));
        $set('suggested_price_display', number_format($suggestedPrice));

        if ($totalCost > 0) {
            $profit = $suggestedPrice - $totalCost;
            $profitPercent = round(($profit / $totalCost) * 100, 1);
            $set('profit_calculation_display', number_format($profit) . 'đ (' . $profitPercent . '%)');
        } else {
            $set('profit_calculation_display', '0');
        }

        if (property_exists($livewire, 'data')) {
            $livewire->data['actual_profit_rate'] = $actualProfitRate;
            $livewire->data['calculated_suggested_price'] = $suggestedPrice;
        }
    }
}
