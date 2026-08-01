<?php

namespace App\Filament\Resources;

use App\Models\User;
use App\Models\Profile;
use App\Models\Document;
use App\Enums\UserRole;
use App\Enums\DocumentStatus;
use App\Enums\DocumentType;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Forms;
use Illuminate\Database\Eloquent\Builder;
use App\Filament\Resources\NannyVerificationResource\Pages;

class NannyVerificationResource extends Resource
{
    protected static ?string $model = User::class;

    protected static ?string $navigationIcon = 'heroicon-o-shield-check';
    protected static ?string $navigationLabel = 'Проверка нянь (Карточки)';
    protected static ?string $pluralModelLabel = 'Карточки и Документы Нянь';
    protected static ?string $modelLabel = 'Карточка няни';

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->where('role', UserRole::NANNY)
            ->with(['profile.documents']);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('profile.first_name')
                    ->label('Имя')
                    ->searchable(query: function ($query, string $search) {
                        return $query->whereHas('profile', fn ($q) => $q->where('first_name', 'ilike', "%{$search}%"));
                    }),

                Tables\Columns\TextColumn::make('profile.last_name')
                    ->label('Фамилия')
                    ->searchable(query: function ($query, string $search) {
                        return $query->whereHas('profile', fn ($q) => $q->where('last_name', 'ilike', "%{$search}%"));
                    }),

                Tables\Columns\TextColumn::make('phone')
                    ->label('Телефон')
                    ->searchable(),

                Tables\Columns\TextColumn::make('profile.city')
                    ->label('Город')
                    ->default('Алматы'),

                Tables\Columns\IconColumn::make('profile.is_verified')
                    ->label('Верификация профиля')
                    ->boolean(),

                Tables\Columns\TextColumn::make('uploaded_docs_count')
                    ->label('Загружено документов')
                    ->getStateUsing(function (User $record) {
                        $count = $record->profile?->documents->count() ?? 0;
                        return "{$count} из 5";
                    })
                    ->badge()
                    ->color(fn ($state) => str_starts_with($state, '5') ? 'success' : 'warning'),

                Tables\Columns\TextColumn::make('pending_docs_count')
                    ->label('На проверке')
                    ->getStateUsing(function (User $record) {
                        $pending = $record->profile?->documents->where('status', DocumentStatus::PENDING)->count() ?? 0;
                        return "{$pending} на проверке";
                    })
                    ->badge()
                    ->color(fn ($state) => !str_starts_with($state, '0') ? 'danger' : 'gray'),
            ])
            ->actions([
                Tables\Actions\Action::make('verify_nanny')
                    ->label('Проверить карточку и документы')
                    ->icon('heroicon-o-folder-open')
                    ->color('primary')
                    ->modalHeading(fn (User $record) => "Карточка няни: " . ($record->profile ? ($record->profile->first_name . ' ' . $record->profile->last_name) : $record->phone))
                    ->modalWidth('4xl')
                    ->modalSubmitAction(false)
                    ->modalCancelActionLabel('Закрыть')
                    ->form(function (User $record) {
                        $profile = $record->profile;
                        $documents = $profile?->documents ?? collect();

                        $documentTypes = [
                            'identity_card' => '🪪 Удостоверение личности (фото)',
                            'criminal_record' => '📋 Справка о несудимости',
                            'medical_clearance' => '🏥 Медицинская справка',
                            'narcology_clearance' => '🧪 Справка с наркоучёта',
                            'psychiatry_clearance' => '🧠 Справка с психоучёта',
                        ];

                        $sections = [
                            Forms\Components\Section::make('Информация о няне')
                                ->schema([
                                    Forms\Components\Placeholder::make('nanny_info')
                                        ->label('')
                                        ->content(function () use ($record, $profile) {
                                            $name = $profile ? ($profile->first_name . ' ' . $profile->last_name) : 'Няня';
                                            $phone = $record->phone ?? '—';
                                            $city = $profile->city ?? 'Алматы';
                                            $exp = $profile->experience_years ? ($profile->experience_years . ' лет') : 'Не указан';
                                            $rate = $profile->hourly_rate ? ($profile->hourly_rate . ' ₸/час') : '—';
                                            $verified = $profile?->is_verified ? '🟢 Профиль ВЕРИФИЦИРОВАН' : '🔴 Профиль НЕ верифицирован';

                                            return new \Illuminate\Support\HtmlString("
                                                <div style='padding: 12px; background: #f8fafc; border-radius: 8px; font-size: 0.95rem; line-height: 1.6;'>
                                                    <div style='font-size: 1.1rem; font-weight: bold; margin-bottom: 6px;'>{$name} ({$phone})</div>
                                                    <div><strong>Город:</strong> {$city} | <strong>Опыт:</strong> {$exp} | <strong>Ставка:</strong> {$rate}</div>
                                                    <div style='margin-top: 6px; font-weight: 600;'>{$verified}</div>
                                                </div>
                                            ");
                                        }),
                                ])
                        ];

                        foreach ($documentTypes as $typeKey => $typeLabel) {
                            $doc = $documents->firstWhere('type.value', $typeKey) ?? $documents->firstWhere('type', $typeKey);

                            $statusBadge = '<span style="color: #64748b; font-weight: 600;">Не загружен</span>';
                            $rejectionInfo = '';
                            $filePath = $doc?->file_path;

                            if ($doc) {
                                $statusVal = is_object($doc->status) ? $doc->status->value : (string) $doc->status;
                                if ($statusVal === 'approved') {
                                    $statusBadge = '<span style="color: #16a34a; font-weight: bold;">🟢 ОДОБРЕН</span>';
                                } elseif ($statusVal === 'rejected') {
                                    $statusBadge = '<span style="color: #dc2626; font-weight: bold;">🔴 ОТКЛОНЁН</span>';
                                    if ($doc->rejection_reason) {
                                        $rejectionInfo = "<div style='color: #dc2626; margin-top: 4px; font-size: 0.85rem;'><strong>Причина отказа:</strong> {$doc->rejection_reason}</div>";
                                    }
                                } else {
                                    $statusBadge = '<span style="color: #d97706; font-weight: bold;">🟡 НА ПРОВЕРКЕ</span>';
                                }
                            }

                            $viewButton = $filePath 
                                ? "<a href='" . asset('storage/' . $filePath) . "' target='_blank' style='display: inline-block; background: #2563eb; color: white; padding: 6px 12px; border-radius: 6px; text-decoration: none; font-size: 0.85rem; font-weight: 600; margin-right: 8px;'>👁️ Открыть документ</a>"
                                : "<span style='color: #94a3b8; font-size: 0.85rem;'>Файл не загружен</span>";

                            $sections[] = Forms\Components\Section::make($typeLabel)
                                ->schema([
                                    Forms\Components\Placeholder::make("doc_{$typeKey}_status")
                                        ->label('')
                                        ->content(new \Illuminate\Support\HtmlString("
                                            <div style='display: flex; justify-content: space-between; align-items: center; padding: 8px 0;'>
                                                <div>
                                                    <div>Status: {$statusBadge}</div>
                                                    {$rejectionInfo}
                                                </div>
                                                <div>
                                                    {$viewButton}
                                                </div>
                                            </div>
                                        ")),
                                ])
                                ->collapsible();
                        }

                        return $sections;
                    }),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('is_verified')
                    ->label('Статус верификации')
                    ->options([
                        '1' => 'Верифицированные',
                        '0' => 'Не верифицированные',
                    ])
                    ->query(function (Builder $query, array $data) {
                        if ($data['value'] === '1') {
                            $query->whereHas('profile', fn ($q) => $q->where('is_verified', true));
                        } elseif ($data['value'] === '0') {
                            $query->whereHas('profile', fn ($q) => $q->where('is_verified', false));
                        }
                    }),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListNannyVerifications::route('/'),
        ];
    }
}
