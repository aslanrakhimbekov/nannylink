<?php

namespace App\Filament\Pages\Auth;

use Filament\Pages\Auth\Login as BaseLogin;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
use DanHarrin\LivewireRateLimiting\Exceptions\TooManyRequestsException;

class Login extends BaseLogin
{
    public ?array $data = [];

    public function mount(): void
    {
        if (Auth::check()) {
            redirect()->intended(filament()->getUrl());
        }

        $this->form->fill([
            'email' => 'aslan.rakhimbekov@gmail.com',
            'password' => '',
        ]);
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                TextInput::make('email')
                    ->label('Email администратора')
                    ->email()
                    ->placeholder('aslan.rakhimbekov@gmail.com')
                    ->required()
                    ->autofocus(),
                TextInput::make('password')
                    ->label('Пароль')
                    ->password()
                    ->required(),
            ])
            ->statePath('data');
    }

    public function authenticate(): ?\Filament\Http\Responses\Auth\Contracts\LoginResponse
    {
        try {
            $this->rateLimit(5);
        } catch (TooManyRequestsException $exception) {
            $this->getRateLimitedNotification($exception)?->send();
            return null;
        }

        $data = $this->form->getState();

        $email = trim(strtolower($data['email'] ?? ''));
        $password = $data['password'] ?? '';

        $user = User::where('email', $email)->first();

        $roleVal = is_object($user?->role) ? $user->role->value : (string) $user?->role;

        if (!$user || !in_array($roleVal, ['admin', 'moderator'])) {
            throw ValidationException::withMessages([
                'data.email' => 'Администратор с таким Email не найден или недостаточно прав.',
            ]);
        }

        if (!$user->password || !Hash::check($password, $user->password)) {
            throw ValidationException::withMessages([
                'data.password' => 'Неверный пароль.',
            ]);
        }

        Auth::login($user, true);

        session()->regenerate();

        return app(\Filament\Http\Responses\Auth\Contracts\LoginResponse::class);
    }
}
