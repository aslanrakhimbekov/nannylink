<?php

namespace App\Filament\Resources\UserResource\Pages;

use App\Filament\Resources\UserResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditUser extends EditRecord
{
    protected static string $resource = UserResource::class;

    protected function mutateFormDataBeforeFill(array $data): array
    {
        $user = $this->record;
        if ($user->profile) {
            $data['first_name'] = $user->profile->first_name;
            $data['last_name'] = $user->profile->last_name;
            $data['city'] = $user->profile->city;
        }
        return $data;
    }

    protected function afterSave(): void
    {
        $data = $this->form->getRawState();
        $user = $this->record;

        $user->profile()->updateOrCreate(
            ['user_id' => $user->id],
            [
                'first_name' => $data['first_name'] ?? ($user->profile?->first_name ?? 'Пользователь'),
                'last_name' => $data['last_name'] ?? ($user->profile?->last_name ?? ''),
                'city' => $data['city'] ?? ($user->profile?->city ?? 'Алматы'),
            ]
        );
    }

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
