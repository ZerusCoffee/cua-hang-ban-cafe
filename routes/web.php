<?php

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/test-notification', function () {
    $admin = \App\Models\User::role('super_admin')->first();

    \Filament\Notifications\Notification::make()
        ->title('🛒 Đơn hàng mới!')
        ->body('Có đơn hàng vừa được tạo')
        ->icon('heroicon-o-shopping-bag')
        ->broadcast($admin);

    return 'Đã gửi notification!';
});
