<?php

namespace App\Filament\Resources;

use App\Models\Document;
use App\Enums\DocumentStatus;
use App\Filament\Resources\DocumentResource\Pages;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Forms;

class DocumentResource extends Resource
{
    protected static ?string $model = Document::class;

    protected static ?string $navigationIcon = 'heroicon-o-document-text';

    public static function canViewAny(): bool
    {
        return true;
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('profile.user.phone')
                    ->label('Телефон')
                    ->searchable(),
                Tables\Columns\TextColumn::make('profile.user.profile.first_name')
                    ->label('Имя'),
                Tables\Columns\TextColumn::make('type')
                    ->label('Тип документа')
                    ->formatStateUsing(fn ($state): string => match ($state instanceof \App\Enums\DocumentType ? $state->value : (string) $state) {
                        'criminal_record' => '📋 Справка о несудимости',
                        'medical_clearance' => '🏥 Медицинская справка',
                        'identity_card' => '🪪 Удостоверение личности',
                        'narcology_clearance' => '🧪 Справка с наркоучёта',
                        'psychiatry_clearance' => '🧠 Справка с психоучёта',
                        default => (string) $state,
                    }),
                Tables\Columns\TextColumn::make('status')
                    ->label('Статус')
                    ->badge()
                    ->color(fn ($state): string => match ($state instanceof \App\Enums\DocumentStatus ? $state->value : (string) $state) {
                        'pending' => 'warning',
                        'approved' => 'success',
                        'rejected' => 'danger',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn ($state): string => match ($state instanceof \App\Enums\DocumentStatus ? $state->value : (string) $state) {
                        'pending' => 'На проверке',
                        'approved' => 'Одобрен',
                        'rejected' => 'Отклонён',
                        default => (string) $state,
                    }),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Загружен')
                    ->dateTime('d.m.Y H:i')
                    ->sortable(),
            ])
            ->actions([
                Tables\Actions\Action::make('open_file')
                    ->label('Открыть документ')
                    ->icon('heroicon-o-eye')
                    ->color('info')
                    ->url(fn (Document $record) => asset('storage/' . $record->file_path))
                    ->openUrlInNewTab(),
                Tables\Actions\Action::make('approve')
                    ->label('Одобрить')
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->requiresConfirmation()
                    ->action(function (Document $record) {
                        if (!auth()->check() || !in_array(auth()->user()->role->value, ['admin', 'moderator'])) {
                            throw new \Illuminate\Auth\Access\AuthorizationException('This action is unauthorized.');
                        }
                        $record->update([
                            'status' => DocumentStatus::APPROVED,
                            'verified_at' => now(),
                            'verified_by_user_id' => auth()->id(),
                        ]);

                        $profile = $record->profile;
                        if ($profile) {
                            $hasPendingOrRejected = Document::where('profile_id', $profile->id)
                                ->where('status', '!=', DocumentStatus::APPROVED)
                                ->exists();

                            if (!$hasPendingOrRejected) {
                                $profile->update(['is_verified' => true]);
                            }

                            $user = $profile->user;
                            if ($user) {
                                $user->notify(new \App\Notifications\DocumentStatusChangedNotification($record));
                            }
                        }
                    })
                    ->visible(fn (Document $record) => $record->status->value !== 'approved'),
                Tables\Actions\Action::make('reject')
                    ->label('Отклонить')
                    ->icon('heroicon-o-x-circle')
                    ->color('danger')
                    ->form([
                        Forms\Components\Textarea::make('rejection_reason')
                            ->label('Причина отклонения')
                            ->required()
                            ->placeholder('Укажите причину отклонения...'),
                    ])
                    ->action(function (Document $record, array $data) {
                        if (!auth()->check() || !in_array(auth()->user()->role->value, ['admin', 'moderator'])) {
                            throw new \Illuminate\Auth\Access\AuthorizationException('This action is unauthorized.');
                        }
                        $record->update([
                            'status' => DocumentStatus::REJECTED,
                            'rejection_reason' => $data['rejection_reason'],
                        ]);

                        $profile = $record->profile;
                        if ($profile) {
                            $profile->update(['is_verified' => false]);
                            
                            $user = $profile->user;
                            if ($user) {
                                $user->notify(new \App\Notifications\DocumentStatusChangedNotification($record));
                            }
                        }
                    })
                    ->visible(fn (Document $record) => $record->status->value !== 'rejected'),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->options([
                        'pending' => 'На проверке',
                        'approved' => 'Одобрен',
                        'rejected' => 'Отклонён',
                    ]),
                Tables\Filters\SelectFilter::make('type')
                    ->options([
                        'criminal_record' => 'Справка о несудимости',
                        'medical_clearance' => 'Медицинская справка',
                        'identity_card' => 'Удостоверение личности',
                        'narcology_clearance' => 'Справка с наркоучёта',
                        'psychiatry_clearance' => 'Справка с психоучёта',
                    ]),
            ])
            ->defaultSort('created_at', 'desc');
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListDocuments::route('/'),
        ];
    }
}
