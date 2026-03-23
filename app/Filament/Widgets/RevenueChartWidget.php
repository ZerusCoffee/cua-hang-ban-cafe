<?php

namespace App\Filament\Widgets;

use BezhanSalleh\FilamentShield\Traits\HasWidgetShield;
use Filament\Widgets\ChartWidget;
use App\Models\OrderProfitLog;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class RevenueChartWidget extends ChartWidget
{
    use HasWidgetShield;
    protected static ?int $sort = 4;

    protected ?string $heading = 'Doanh thu & Lợi nhuận';

    public ?string $filter = null;

    protected function getFilters(): ?array
    {
        // Lấy tháng sớm nhất có dữ liệu
        $oldest = OrderProfitLog::min('logged_at');
        if (!$oldest) return [];

        $start = Carbon::parse($oldest)->startOfMonth();
        $end   = Carbon::now()->startOfMonth();

        $filters = [];
        $current = $end->copy();

        while ($current->gte($start)) {
            $key   = $current->format('Y-m');
            $label = $current->format('m/Y');
            $filters[$key] = $label;
            $current->subMonth();
        }

        return $filters;
    }

    protected function getData(): array
    {
        // Mặc định tháng hiện tại nếu chưa chọn
        $month = $this->filter ?? now()->format('Y-m');

        $start = Carbon::parse($month)->startOfMonth();
        $end   = Carbon::parse($month)->endOfMonth();

        $data = OrderProfitLog::whereBetween('logged_at', [$start, $end])
            ->select(
                DB::raw('DATE(logged_at) as date'),
                DB::raw('SUM(total_price) as revenue'),
                DB::raw('SUM(total_profit) as profit')
            )
            ->groupBy('date')
            ->orderBy('date')
            ->get();

        return [
            'datasets' => [
                [
                    'label'           => 'Doanh thu',
                    'data'            => $data->pluck('revenue')->map(fn($v) => round($v))->toArray(),
                    'borderColor'     => '#10b981',
                    'backgroundColor' => '#10b98133',
                    'fill'            => true,
                    'tension'         => 0.4,
                ],
                [
                    'label'           => 'Lợi nhuận',
                    'data'            => $data->pluck('profit')->map(fn($v) => round($v))->toArray(),
                    'borderColor'     => '#f59e0b',
                    'backgroundColor' => '#f59e0b33',
                    'fill'            => true,
                    'tension'         => 0.4,
                ],
            ],
            'labels' => $data->pluck('date')
                ->map(fn($d) => Carbon::parse($d)->format('d/m'))
                ->toArray(),
        ];
    }

    protected function getType(): string
    {
        return 'line';
    }
}
