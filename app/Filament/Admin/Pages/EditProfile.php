<?php

namespace App\Filament\Admin\Pages;

use Filament\Auth\Pages\EditProfile as BaseEditProfile;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;

class EditProfile extends BaseEditProfile
{
    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                $this->getNameFormComponent(),
                $this->getEmailFormComponent(),
                $this->getPasswordFormComponent(),
                $this->getPasswordConfirmationFormComponent(),

                Toggle::make('notify_email')
                    ->label('Email Notifications')
                    ->default(true),

                Toggle::make('notify_telegram')
                    ->label('Telegram Notifications')
                    ->default(true),
            ]);
    }
}
