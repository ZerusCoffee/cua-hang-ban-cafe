<?php

namespace App\Providers\Filament;

use App\Filament\Pages\ProductExportReportPage;
use App\Filament\Pages\ProductImportBatchPricePage;
use App\Filament\Pages\WarehousePage;
use App\Filament\Widgets\CafeStatsOverview;
use App\Filament\Widgets\RevenueChartWidget;
use App\Filament\Widgets\RecentImportsWidget;
use App\Filament\Widgets\TopProductsWidget;
use BezhanSalleh\FilamentShield\FilamentShieldPlugin;
use Filament\Http\Middleware\Authenticate;
use Filament\Http\Middleware\AuthenticateSession;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Pages\Dashboard;
use Filament\Panel;
use Filament\PanelProvider;
use Filament\Support\Colors\Color;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\View\Middleware\ShareErrorsFromSession;

class AdminPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        return $panel
            ->default()
            ->id('admin')
            ->path('admin')
            ->login()
            ->authGuard('web')
            ->authPasswordBroker('users')
            ->colors([
                'primary' => Color::Amber,
            ])
            ->discoverResources(in: app_path('Filament/Resources'), for: 'App\Filament\Resources')
            ->discoverPages(in: app_path('Filament/Pages'), for: 'App\Filament\Pages')
            ->pages([
                Dashboard::class,
                WarehousePage::class,
                ProductExportReportPage::class,
                ProductImportBatchPricePage::class
            ])
            ->widgets([
                CafeStatsOverview::class,
                TopProductsWidget::class,
                RevenueChartWidget::class,
                RecentImportsWidget::class,
            ])
            ->middleware([
                EncryptCookies::class,
                AddQueuedCookiesToResponse::class,
                StartSession::class,
                AuthenticateSession::class,
                ShareErrorsFromSession::class,
                VerifyCsrfToken::class,
                SubstituteBindings::class,
                DisableBladeIconComponents::class,
                DispatchServingFilamentEvent::class,
            ])
            ->authMiddleware([
                Authenticate::class,
            ])
            ->plugins([
                FilamentShieldPlugin::make()
                    ->navigationLabel('Phân quyền')
                    ->navigationGroup('Hệ thống')
                    ->navigationSort(99)
                    ->gridColumns([
                        'default' => 1,
                        'sm' => 2,
                        'lg' => 3,
                    ])
                    ->checkboxListColumns([
                        'default' => 1,
                        'sm' => 2,
                        'lg' => 4,
                    ]),
            ])
            ->profile(isSimple: false)
            ->broadcasting()
            ->databaseNotifications()
            ->databaseNotificationsPolling('30s');
    }
}
