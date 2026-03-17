<?php

namespace App\Filament\Widgets;

use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use App\Models\OrderProfitLog;
use App\Models\Order;

class CafeStatsOverview extends StatsOverviewWidget
{
    protected function getStats(): array
    {
        $today = today();

        $todayRevenue = OrderProfitLog::whereDate('logged_at', $today)->sum('total_price');
        $todayProfit = OrderProfitLog::whereDate('logged_at', $today)->sum('total_profit');
        $todayOrders = Order::whereDate('created_at', $today)->count();
        $todayCustomers = Order::whereDate('created_at', $today)->distinct('customer_id')->count('customer_id');

        return [
            Stat::make('Doanh thu', number_format($todayRevenue) . 'đ')
                ->description('Hôm nay')
                ->descriptionIcon('heroicon-m-currency-dollar')
                ->color('success')
                ->extraAttributes(['class' => 'text-2xl']),

            Stat::make('Lợi nhuận', number_format($todayProfit) . 'đ')
                ->description('Biên: ' . round(($todayProfit/$todayRevenue)*100, 1) . '%')
                ->descriptionIcon('heroicon-m-banknotes')
                ->color('info'),

            Stat::make('Đơn hàng', $todayOrders)
                ->description('Hôm nay')
                ->descriptionIcon('heroicon-m-shopping-cart')
                ->color('warning'),

            Stat::make('Khách hàng', $todayCustomers)
                ->description('Lượt khách')
                ->descriptionIcon('heroicon-m-user-group')
                ->color('danger'),
        ];
    }

    protected function getColumns(): int
    {
        return 4;
    }
}
