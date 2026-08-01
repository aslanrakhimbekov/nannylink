<?php

namespace App\Filament\Resources;

use App\Models\User;
use App\Enums\UserStatus;
use App\Filament\Resources\UserResource\Pages;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Forms;

class UserResource extends Resource
{
    protected static ?string $model = User::class;

    protected static ?string $navigationIcon = 'heroicon-o-users';

    public static function canViewAny(): bool
    {
        return true;
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('phone')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('profile.first_name')
                    ->label('Имя')
                    ->searchable(),
                Tables\Columns\TextColumn::make('profile.last_name')
                    ->label('Фамилия')
                    ->searchable(),
                Tables\Columns\TextColumn::make('role')
                    ->badge()
                    ->color(fn ($state): string => match (is_object($state) ? $state->value : (string) $state) {
                        'admin' => 'danger',
                        'nanny' => 'success',
                        'parent' => 'info',
                        default => 'gray',
                    }),
                Tables\Columns\TextColumn::make('status')
                    ->badge()
                    ->color(fn ($state): string => match (is_object($state) ? $state->value : (string) $state) {
                        'active' => 'success',
                        'blocked' => 'danger',
                        default => 'gray',
                    }),
                Tables\Columns\TextColumn::make('profile.iin')
                    ->label('ИИН')
                    ->searchable(),
                Tables\Columns\TextColumn::make('profile.city')
                    ->label('Город'),
                Tables\Columns\IconColumn::make('profile.is_verified')
                    ->label('Верифицирован')
                    ->boolean(),
                Tables\Columns\TextColumn::make('profile.balance_coins')
                    ->label('Монеты')
                    ->sortable(),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Регистрация')
                    ->dateTime('d.m.Y H:i')
                    ->sortable(),
            ])
            ->actions([
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
                            $profile = \App\Models\Profile::where('user_id', $record->id)
                                ->lockForUpdate()
                                ->first();

                            if ($profile) {
                                $amount = (int) $data['amount'];
                                $profile->increment('balance_coins', $amount);

                                \App\Models\CoinTransaction::create([
                                    'user_id' => $record->id,
                                    'type' => \App\Enums\CoinTransactionType::DEPOSIT,
                                    'amount' => $amount,
                                ]);
                            }
                        });
                    }),
                Tables\Actions\Action::make('block')
                    ->label('Заблокировать')
                    ->icon('heroicon-o-no-symbol')
                    ->color('danger')
                    ->requiresConfirmation()
                    ->modalHeading('Заблокировать пользователя')
                    ->modalDescription(fn (User $record) => "Вы уверены, что хотите заблокировать {$record->phone}? Пользователь не сможет войти в систему.")
                    ->form([
                        Forms\Components\Textarea::make('block_reason')
                            ->label('Причина блокировки')
                            ->required()
                            ->placeholder('Укажите причину блокировки...'),
                    ])
                    ->action(function (User $record, array $data) {
                        $record->update([
                            'status' => UserStatus::BLOCKED,
                        ]);
                        // Revoke all tokens so user is logged out immediately
                        $record->tokens()->delete();
                    })
                    ->visible(fn (User $record) => $record->status === UserStatus::ACTIVE),
                Tables\Actions\Action::make('unblock')
                    ->label('Разблокировать')
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->requiresConfirmation()
                    ->modalHeading('Разблокировать пользователя')
                    ->modalDescription(fn (User $record) => "Разблокировать {$record->phone}? Пользователь сможет снова войти в систему.")
                    ->action(function (User $record) {
                        $record->update([
                            'status' => UserStatus::ACTIVE,
                        ]);
                    })
                    ->visible(fn (User $record) => $record->status === UserStatus::BLOCKED),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('role')
                    ->options([
                        'parent' => 'Родитель',
                        'nanny' => 'Няня',
                        'admin' => 'Админ',
                    ]),
                Tables\Filters\SelectFilter::make('status')
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
        ];
    }
}
