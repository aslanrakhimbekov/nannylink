<?php

namespace App\Filament\Pages\Auth;

use Filament\Pages\Auth\Login as BaseLogin;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;

class Login extends BaseLogin
{
    public function form(Form $form): Form
    {
        return $form
            ->schema([
                TextInput::make('phone')
                    ->label('Номер телефона администратора')
                    ->placeholder('+77014444444')
                    ->required()
                    ->autofocus(),
                TextInput::make('code')
                    ->label('Код авторизации (1111)')
                    ->password()
                    ->required()
                    ->default('1111'),
            ]);
    }

    public function authenticate(): ?\Filament\Http\Responses\Auth\Contracts\LoginResponse
    {
        $data = $this->form->getState();

        $phone = trim($data['phone']);
        if (!str_starts_with($phone, '+')) {
            $phone = '+' . preg_replace('/[^0-9]/', '', $phone);
        }

        $user = User::where('phone', $phone)->first();

        $roleVal = is_object($user?->role) ? $user->role->value : (string) $user?->role;

        if (!$user || !in_array($roleVal, ['admin', 'moderator'])) {
            throw ValidationException::withMessages([
                'data.phone' => 'Пользователь не найден или у вас нет прав администратора.',
            ]);
        }

        if ($data['code'] !== '1111') {
            throw ValidationException::withMessages([
                'data.code' => 'Неверный код (используйте 1111).',
            ]);
        }

        Auth::login($user, true);

        session()->regenerate();

        return app(\Filament\Http\Responses\Auth\Contracts\LoginResponse::class);
    }
}
