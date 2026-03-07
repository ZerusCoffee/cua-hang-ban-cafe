<?php

namespace App\Filament\Resources\Products\Schemas;

use App\Models\Ingredient;
use App\Models\Option;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
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

                            RichEditor::make('description')
                                ->label('Mô tả chi tiết')
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
                                        ->afterStateUpdated(function ($state, $set, $get, $livewire) {
                                            if ($state) {
                                                $images = $livewire->data['images'] ?? [];
                                                foreach (array_keys($images) as $key) {
                                                    $livewire->data['images'][$key]['is_primary'] = false;
                                                }
                                                $set('is_primary', true);
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
                                            self::updateItemCost($set, $get);
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
                                            self::updateItemCost($set, $get);
                                            self::updateAllDisplays($set, $livewire);
                                        }),

                                    TextInput::make('ingredient_cost_display')
                                        ->label('Thành tiền')
                                        ->disabled()
                                        ->dehydrated(false)
                                        ->suffix('đ')
                                        ->afterStateHydrated(function (TextInput $component, Get $get) {
                                            $component->state(self::calculateItemCost($get));
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

                    Step::make('Tùy chọn sản phẩm')
                        ->icon('heroicon-o-adjustments-horizontal')
                        ->schema([
                            // Các option và giá
                            Section::make('Cấu hình giá theo option')
                                ->description('Thiết lập giá cho từng tùy chọn (VD: Size L +5000đ)')
                                ->schema([
                                    Repeater::make('productOptions')
                                        ->label('Danh sách tùy chọn')
                                        ->relationship('productOptions')
                                        ->schema([
                                            Select::make('option_id')
                                                ->label('Tùy chọn')
                                                ->options(function () {
                                                    return Option::query()
                                                        ->with('group')
                                                        ->get()
                                                        ->mapWithKeys(fn($opt) => [
                                                            $opt->id => sprintf(
                                                                '[%s] %s',
                                                                $opt->group->name,
                                                                $opt->value
                                                            )
                                                        ])
                                                        ->toArray();
                                                })
                                                ->searchable()
                                                ->preload()
                                                ->required()
                                                ->distinct()
                                                ->disableOptionsWhenSelectedInSiblingRepeaterItems()
                                                ->columnSpan(2)
                                                ->reactive()
                                                ->afterStateUpdated(function ($state, Set $set) {
                                                    // Có thể set giá mặc định theo group nếu muốn
                                                    if ($state) {
                                                        $option = Option::with('group')->find($state);
                                                        // Ví dụ: Size L thường +5000, Size XL +10000
                                                        if ($option && $option->group->name === 'Size') {
                                                            if (str_contains($option->value, 'L')) {
                                                                $set('additional_price', 5000);
                                                            } elseif (str_contains($option->value, 'XL')) {
                                                                $set('additional_price', 10000);
                                                            }
                                                        }
                                                    }
                                                }),

                                            TextInput::make('additional_price')
                                                ->label('Giá thêm')
                                                ->numeric()
                                                ->prefix('đ')
                                                ->default(0)
                                                ->minValue(0)
                                                ->step(1000)
                                                ->columnSpan(1)
                                                ->helperText('Số tiền cộng thêm khi chọn option này'),
                                        ])
                                        ->columns(3)
                                        ->addActionLabel('+ Thêm tùy chọn')
                                        ->reorderable()
                                        ->collapsible()
                                        ->defaultItems(0)
                                        ->columnSpanFull(),
                                ]),

                            // Modifiers điều chỉnh nguyên liệu
                            Section::make('Điều chỉnh nguyên liệu theo option')
                                ->description('Cài đặt lượng nguyên liệu thay đổi khi chọn option (VD: size L thêm 20% cafe)')
                                ->schema([
                                    Repeater::make('optionModifiers')
                                        ->label('Modifiers')
                                        ->relationship('optionModifiers')
                                        ->schema([
                                            Select::make('option_id')
                                                ->label('Áp dụng cho option')
                                                ->options(function () {
                                                    return Option::query()
                                                        ->with('group')
                                                        ->get()
                                                        ->mapWithKeys(fn($opt) => [
                                                            $opt->id => sprintf(
                                                                '[%s] %s',
                                                                $opt->group->name,
                                                                $opt->value
                                                            )
                                                        ])
                                                        ->toArray();
                                                })
                                                ->searchable()
                                                ->preload()
                                                ->required()
                                                ->columnSpan(2),

                                            Select::make('ingredient_id')
                                                ->label('Nguyên liệu')
                                                ->relationship('ingredient', 'name')
                                                ->searchable()
                                                ->preload()
                                                ->required()
                                                ->columnSpan(2),

                                            TextInput::make('deltaQuantity')
                                                ->label('Thay đổi')
                                                ->numeric()
                                                ->step(0.01)
                                                ->required()
                                                ->suffix('%')
                                                ->default(0)
                                                ->helperText('Số % thêm vào (âm nếu giảm)')
                                                ->columnSpan(1),
                                        ])
                                        ->columns(5)
                                        ->addActionLabel('+ Thêm modifier')
                                        ->reorderable()
                                        ->collapsible()
                                        ->defaultItems(0)
                                        ->columnSpanFull()
                                ]),

                            // Xem trước cấu hình
                            Section::make('Xem trước cấu hình')
                                ->schema([
                                    TextEntry::make('preview_options')
                                        ->label('Các tùy chọn theo nhóm')
                                        ->state(function ($get) {
                                            $productOptions = $get('productOptions') ?? [];
                                            if (empty($productOptions)) {
                                                return '⚠️ Chưa có tùy chọn nào';
                                            }

                                            // Nhóm theo group
                                            $grouped = [];
                                            foreach ($productOptions as $item) {
                                                if (empty($item['option_id'])) continue;

                                                $option = Option::with('group')->find($item['option_id']);
                                                if (!$option) continue;

                                                $groupName = $option->group->name;
                                                if (!isset($grouped[$groupName])) {
                                                    $grouped[$groupName] = [];
                                                }

                                                $price = (int)($item['additional_price'] ?? 0);
                                                $priceText = $price > 0 ? ' +' . number_format($price) . 'đ' : ' (mặc định)';
                                                $grouped[$groupName][] = "• {$option->value}{$priceText}";
                                            }

                                            // Format output
                                            $result = [];
                                            foreach ($grouped as $group => $options) {
                                                $result[] = "📌 {$group}:";
                                                $result = array_merge($result, $options);
                                                $result[] = '';
                                            }

                                            return implode("\n", $result);
                                        })
                                        ->columnSpanFull(),

                                    TextEntry::make('preview_modifiers')
                                        ->label('Điều chỉnh nguyên liệu')
                                        ->state(function ($get) {
                                            $modifiers = $get('optionModifiers') ?? [];
                                            if (empty($modifiers)) {
                                                return '⚠️ Chưa có modifier nào';
                                            }

                                            $result = [];
                                            foreach ($modifiers as $mod) {
                                                if (empty($mod['option_id']) || empty($mod['ingredient_id'])) continue;

                                                $option = Option::with('group')->find($mod['option_id']);
                                                $ingredient = Ingredient::find($mod['ingredient_id']);

                                                if ($option && $ingredient) {
                                                    $delta = floatval($mod['deltaQuantity'] ?? 0);
                                                    $sign = $delta > 0 ? '+' : '';
                                                    $result[] = "• [{$option->group->name} {$option->value}] {$ingredient->name}: {$sign}{$delta}%";
                                                }
                                            }

                                            return implode("\n", $result) ?: '⚠️ Chưa có modifier nào';
                                        })
                                        ->columnSpanFull(),
                                ]),
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
                                        $component->state('Chưa có nguyên liệu');
                                    }
                                }),
                        ])
                        ->columns(2),
                ])
                ->columnSpanFull()
                ->skippable()
                ->live()
                ->afterStateUpdated(function (Set $set, Get $get, Livewire $livewire) {
                    self::updateAllDisplays($set, $livewire);
                }),
            ]);
    }

    /**
     * Tính thành tiền của 1 nguyên liệu
     */
    private static function calculateItemCost(Get $get): string
    {
        $ingredientId = $get('ingredient_id');
        $amount = floatval($get('amount') ?? 0);

        if (!$ingredientId || $amount <= 0) {
            return '0';
        }

        $ingredient = Ingredient::find($ingredientId);
        if (!$ingredient) {
            return '0';
        }

        return number_format($ingredient->cost_price * $amount);
    }

    /**
     * Update thành tiền của 1 nguyên liệu
     */
    private static function updateItemCost(Set $set, Get $get): void
    {
        $set('ingredient_cost_display', self::calculateItemCost($get));
    }

    /**
     * Tính tổng cost từ Get (dùng trong form)
     */
    private static function calculateTotalCostFromGet(Get $get): float
    {
        $recipeDetails = $get('recipeDetails') ?? [];
        $total = 0;

        foreach ($recipeDetails as $detail) {
            if (empty($detail['ingredient_id']) || empty($detail['amount'])) {
                continue;
            }

            $ingredient = Ingredient::find($detail['ingredient_id']);
            if ($ingredient) {
                $total += floatval($ingredient->cost_price) * floatval($detail['amount']);
            }
        }

        return round($total, 2);
    }

    /**
     * Tính tổng cost từ Livewire data (dùng trong update)
     */
    private static function calculateTotalCostFromLivewire(Livewire $livewire): float
    {
        $recipeDetails = $livewire->data['recipeDetails'] ?? [];
        $total = 0;

        foreach ($recipeDetails as $detail) {
            if (empty($detail['ingredient_id']) || empty($detail['amount'])) {
                continue;
            }

            $ingredient = Ingredient::find($detail['ingredient_id']);
            if ($ingredient) {
                $total += floatval($ingredient->cost_price) * floatval($detail['amount']);
            }
        }

        return round($total, 2);
    }

    /**
     * Tính giá đề xuất (làm tròn 1000)
     */
    private static function calculateSuggestedPrice(float $totalCost, float $profitRate): int
    {
        if ($totalCost <= 0) {
            return 0;
        }

        $price = $totalCost * (1 + $profitRate / 100);
        return (int) ceil($price / 1000) * 1000;
    }

    /**
     * Update tất cả các trường hiển thị
     */
    private static function updateAllDisplays(Set $set, Livewire $livewire): void
    {
        $totalCost = self::calculateTotalCostFromLivewire($livewire);
        $profitRateInput = floatval($livewire->data['profit_rate_input'] ?? 30);
        $suggestedPrice = self::calculateSuggestedPrice($totalCost, $profitRateInput);

        // Cập nhật các trường hiển thị
        $set('total_cost_final_display', number_format($totalCost));
        $set('suggested_price_display', number_format($suggestedPrice));

        // Cập nhật profit_calculation_display
        if ($totalCost > 0) {
            $profit = $suggestedPrice - $totalCost;
            $profitPercent = round(($profit / $totalCost) * 100, 1);
            $set('profit_calculation_display', number_format($profit) . 'đ (' . $profitPercent . '%)');
        } else {
            $set('profit_calculation_display', 'Chưa có nguyên liệu');
        }

        // Tính profit rate thực tế để lưu
        if ($totalCost > 0 && $suggestedPrice > 0) {
            $actualProfitRate = round(($suggestedPrice - $totalCost) / $totalCost * 100, 2);

            // Lưu vào data để xử lý trong beforeSave/mutateFormDataBeforeCreate
            if (property_exists($livewire, 'data')) {
                $livewire->data['actual_profit_rate'] = $actualProfitRate;
                $livewire->data['calculated_suggested_price'] = $suggestedPrice;
            }
        }
    }
}
