<?php

namespace App\Filament\Resources\Users\Pages;

use App\Filament\Resources\Users\UserResource;
use Filament\Actions\ViewAction;
use Filament\Resources\Pages\EditRecord;

class EditUser extends EditRecord
{
    protected static string $resource = UserResource::class;

    protected function mutateFormDataBeforeFill(array $data): array
    {
        $data['email_verified'] = ! empty($data['email_verified_at']);

        return $data;
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        if (! empty($data['email_verified'])) {
            $data['email_verified_at'] = $this->record->email_verified_at ?? now();
        } else {
            $data['email_verified_at'] = null;
        }

        unset($data['email_verified'], $data['password_confirmation']);

        if (empty($data['password'])) {
            unset($data['password']);
        }

        $data['is_admin'] = false;

        return $data;
    }

    protected function getHeaderActions(): array
    {
        return [
            ViewAction::make(),
        ];
    }
}