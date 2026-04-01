<?php

namespace App\Filament\Resources\Customers\Schemas;

use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Illuminate\Support\Facades\Hash;

class CustomerForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Thông tin cá nhân')
                ->columns(2)
                ->schema([
                    FileUpload::make('avatar')
                        ->label('Ảnh đại diện')
                        ->image()
                        ->imageEditor()
                        ->disk('public')
                        ->directory('avatars')
                        ->maxSize(2048)
                        ->columnSpanFull(),

                    TextInput::make('name')
                        ->label('Họ và tên')
                        ->required()
                        ->maxLength(255),

                    TextInput::make('email')
                        ->label('Email')
                        ->email()
                        ->required()
                        ->unique(ignoreRecord: true)
                        ->maxLength(255),

                    TextInput::make('phone')
                        ->label('Số điện thoại')
                        ->tel()
                        ->regex('/^0\d{9}$/')   // bắt đầu bằng 0, theo sau đúng 9 chữ số → tổng 10 số
                        ->maxLength(10)          // giới hạn nhập tối đa 10 ký tự
                        ->validationMessages([
                            'regex' => 'Số điện thoại phải có đúng 10 số và bắt đầu bằng số 0.',
                        ]),
                ]),

            Section::make('Mật khẩu')
                ->columns(2)
                ->schema([
                    TextInput::make('password')
                        ->label('Mật khẩu')
                        ->password()
                        ->revealable()
                        ->required(fn(string $operation) => $operation === 'create')
                        ->dehydrated(fn(?string $state) => filled($state))
                        ->dehydrateStateUsing(fn(string $state) => Hash::make($state))
                        ->minLength(8)
                        ->helperText(
                            fn(string $operation) => $operation === 'edit'
                                ? 'Để trống nếu không muốn thay đổi mật khẩu.'
                                : null
                        ),

                    TextInput::make('password_confirmation')
                        ->label('Xác nhận mật khẩu')
                        ->password()
                        ->revealable()
                        ->required(fn(string $operation) => $operation === 'create')
                        ->dehydrated(false)
                        ->same('password'),
                ]),

            Section::make('Trạng thái tài khoản')
                ->schema([
                    Toggle::make('is_locked')
                        ->label('Khoá tài khoản')
                        ->helperText('Bật để ngăn khách hàng đăng nhập.')
                        ->default(false)
                        ->inline(false),
                ]),
        ]);
    }
}
