<?php

namespace App\Filament\Resources\Users\Schemas;

use App\Enums\Role;
use App\Enums\Status;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Group;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Illuminate\Support\Str;

class UserForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Personal Details')
                    ->schema([
                        // Polymorphic Profile Image (Saved to relation)
                        Group::make()
                            ->relationship('image')
                            ->schema([
                                FileUpload::make('image_url')
                                    ->label('Avatar / Profile Photo')
                                    ->image()
                                    ->avatar()
                                    ->imageEditor()
                                    ->directory('users/avatars'),
                            ])
                            ->columnSpanFull(),

                        TextInput::make('firstName')
                            ->maxLength(52),

                        TextInput::make('lastName')
                            ->maxLength(52),

                        TextInput::make('phone')
                            ->tel()
                            ->required()
                            ->maxLength(15)
                            ->unique(ignoreRecord: true),

                        TextInput::make('email')
                            ->email()
                            ->maxLength(52)
                            ->unique(ignoreRecord: true),

                        TextInput::make('password')
                            ->password()
                            ->dehydrated(fn ($state) => filled($state))
                            ->required(fn (string $operation): bool => $operation === 'create')
                            ->maxLength(255),

                        // System random token generator
                        Hidden::make('random_token')
                            ->default(fn () => Str::random(32)),
                    ])->columns(2),

                Section::make('Access & Status')
                    ->schema([
                        Select::make('role')
                            ->options(Role::class)
                            ->required()
                            ->default(Role::USER->value),

                        Select::make('status')
                            ->options(Status::class)
                            ->required()
                            ->default(Status::ACTIVE->value),
                    ])->columns(2),
            ]);
    }
}
