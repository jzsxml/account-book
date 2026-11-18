<?php

namespace App\Filament\Resources\Users\Pages;

use App\Filament\Resources\Users\UserResource;
use App\Services\BookProvisioner;
use Filament\Resources\Pages\CreateRecord;

class CreateUser extends CreateRecord
{
    protected static string $resource = UserResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['is_admin'] = false;
        $data['settings'] = ['timezone' => 'Asia/Shanghai'];

        if (! empty($data['email_verified'])) {
            $data['email_verified_at'] = now();
        } else {
            $data['email_verified_at'] = null;
        }

        unset($data['email_verified'], $data['password_confirmation']);

        return $data;
    }

    protected function afterCreate(): void
    {
        if ($this->record->books()->doesntExist()) {
            app(BookProvisioner::class)->provisionForUser($this->record);
        }
    }
}