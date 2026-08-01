<?php

namespace App\Filament\Resources;

use App\Models\User;
use App\Models\Profile;
use App\Enums\UserStatus;
use App\Filament\Resources\UserResource\Pages;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Forms;
use Filament\Forms\Form;

class UserResource extends Resource
{
    protected static ?string $model = User::class;

    protected static ?string $navigationIcon = 'heroicon-o-users';
    protected static ?string $navigationLabel = 'Пользователи и Админы';
    protected static ?string $pluralModelLabel = 'Пользователи и Администраторы';
    protected static ?string $modelLabel = 'Пользователь';

    public static function canViewAny(): bool
    {
        return true;
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Учетные данные админа / пользователя')
                    ->schema([
                        Forms\Components\TextInput::make('email')
                            ->label('Email (Обязательно для админа)')
                            ->email()
                            ->required(fn (Forms\Get $get) => in_array($get('role'), ['admin', 'moderator']))
                            ->unique(User::class, 'email', ignoreRecord: true)
                            ->placeholder('admin@nannylink.kz'),

                        Forms\Components\TextInput::make('password')
                            ->label('Пароль')
                            ->password()
                            ->dehydrateStateUsing(fn ($state) => \Illuminate\Support\Facades\Hash::make($state))
                            ->dehydrated(fn ($state) => filled($state))
                            ->required(fn (string $operation): bool => $operation === 'create')
                            ->placeholder('Введите новый пароль для изменения'),

                        Forms\Components\Select::make('role')
                            ->label('Роль')
                            ->options([
                                'admin' => 'Администратор',
                                'moderator' => 'Модератор',
                                'nanny' => 'Няня',
                                'parent' => 'Родитель',
                            ])
                            ->default('admin')
                            ->required(),

                        Forms\Components\TextInput::make('phone')
                            ->label('Номер телефона (Необязательно для админов)')
                            ->placeholder('+77014444444')
                            ->nullable(),
                    ])->columns(2),

                Forms\Components\Section::make('Персональные данные (ФИО)')
                    ->schema([
                        Forms\Components\TextInput::make('first_name')
                            ->label('Имя')
                            ->required()
                            ->placeholder('Иван'),

                        Forms\Components\TextInput::make('last_name')
                            ->label('Фамилия')
                            ->required()
                            ->placeholder('Иванов'),

                        Forms\Components\TextInput::make('city')
                            ->label('Город')
                            ->default('Алматы'),
                    ])->columns(3),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('email')
                    ->label('Email')
                    ->searchable()
                    ->sortable()
                    ->default('—'),

                Tables\Columns\TextColumn::make('first_name')
                    ->label('Имя')
                    ->getStateUsing(fn (User $record) => $record->profile?->first_name ?? '—')
                    ->searchable(query: function ($query, string $search) {
                        return $query->whereHas('profile', fn ($q) => $q->where('first_name', 'ilike', "%{$search}%"));
                    }),

                Tables\Columns\TextColumn::make('last_name')
                    ->label('Фамилия')
                    ->getStateUsing(fn (User $record) => $record->profile?->last_name ?? '—')
                    ->searchable(query: function ($query, string $search) {
                        return $query->whereHas('profile', fn ($q) => $q->where('last_name', 'ilike', "%{$search}%"));
                    }),

                Tables\Columns\TextColumn::make('phone')
                    ->label('Телефон')
                    ->searchable()
                    ->default('—'),

                Tables\Columns\TextColumn::make('role')
                    ->label('Роль')
                    ->badge()
                    ->color(fn ($state): string => match (is_object($state) ? $state->value : (string) $state) {
                        'admin' => 'danger',
                        'moderator' => 'warning',
                        'nanny' => 'success',
                        'parent' => 'info',
                        default => 'gray',
                    }),

                Tables\Columns\TextColumn::make('status')
                    ->label('Статус')
                    ->badge()
                    ->color(fn ($state): string => match (is_object($state) ? $state->value : (string) $state) {
                        'active' => 'success',
                        'blocked' => 'danger',
                        default => 'gray',
                    }),

                Tables\Columns\TextColumn::make('city')
                    ->label('Город')
                    ->getStateUsing(fn (User $record) => $record->profile?->city ?? '—'),

                Tables\Columns\TextColumn::make('balance_coins')
                    ->label('Монеты')
                    ->getStateUsing(fn (User $record) => $record->profile?->balance_coins ?? 0)
                    ->sortable(),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Регистрация')
                    ->getStateUsing(fn (User $record) => $record->created_at ? $record->created_at->format('d.m.Y H:i') : '—')
                    ->sortable(),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),

                Tables\Actions\Action::make('topup')
                    ->label('Пополнить')
                    ->icon('heroicon-o-plus-circle')
                    ->color('success')
                    ->form([
                        Forms\Components\TextInput::make('amount')
                            ->label('Сумма (монет)')
                            ->numeric()
                            ->required(),
                    ])
                    ->action(function (User $record, array $data) {
                        \Illuminate\Support\Facades\DB::transaction(function () use ($record, $data) {
                            $profile = Profile::firstOrCreate(
                                ['user_id' => $record->id],
                                ['first_name' => 'Пользователь', 'last_name' => '', 'city' => 'Алматы', 'balance_coins' => 0]
                            );

                            $amount = (int) $data['amount'];
                            $profile->increment('balance_coins', $amount);

                            \App\Models\CoinTransaction::create([
                                'user_id' => $record->id,
                                'type' => \App\Enums\CoinTransactionType::DEPOSIT,
                                'amount' => $amount,
                            ]);
                        });
                    }),

                Tables\Actions\Action::make('block')
                    ->label('Заблокировать')
                    ->icon('heroicon-o-no-symbol')
                    ->color('danger')
                    ->requiresConfirmation()
                    ->modalHeading('Заблокировать пользователя')
                    ->form([
                        Forms\Components\Textarea::make('block_reason')
                            ->label('Причина блокировки')
                            ->required(),
                    ])
                    ->action(function (User $record, array $data) {
                        $record->update(['status' => UserStatus::BLOCKED]);
                        $record->tokens()->delete();
                    })
                    ->visible(fn (User $record) => $record->status === UserStatus::ACTIVE),

                Tables\Actions\Action::make('unblock')
                    ->label('Разблокировать')
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->requiresConfirmation()
                    ->action(function (User $record) {
                        $record->update(['status' => UserStatus::ACTIVE]);
                    })
                    ->visible(fn (User $record) => $record->status === UserStatus::BLOCKED),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('role')
                    ->label('Фильтр по роли')
                    ->options([
                        'admin' => 'Администратор',
                        'moderator' => 'Модератор',
                        'nanny' => 'Няня',
                        'parent' => 'Родитель',
                    ]),
                Tables\Filters\SelectFilter::make('status')
                    ->label('Фильтр по статусу')
                    ->options([
                        'active' => 'Активный',
                        'blocked' => 'Заблокирован',
                    ]),
            ])
            ->defaultSort('created_at', 'desc');
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListUsers::route('/'),
            'create' => Pages\CreateUser::route('/create'),
            'edit' => Pages\EditUser::route('/{record}/edit'),
        ];
    }
}
