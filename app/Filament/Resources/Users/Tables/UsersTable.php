<?php

namespace App\Filament\Resources\Users\Tables;

use App\Enums\Role;
use App\Enums\Status;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class UsersTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                ImageColumn::make('image.image_url')
                    ->label('Photo')
                    ->circular(),

                TextColumn::make('firstName')
                    ->formatStateUsing(fn($record) => trim("{$record->firstName} {$record->lastName}"))
                    ->label('Name')
                    ->searchable(['firstName', 'lastName'])
                    ->sortable(),

                TextColumn::make('phone')
                    ->searchable(),

                TextColumn::make('email')
                    ->searchable(),

                TextColumn::make('role')
                    ->badge(),

                TextColumn::make('status')
                    ->badge()
                    ->color(fn(Status $state): string => match ($state) {
                        Status::ACTIVE => 'success',
                        default => 'danger',
                    }),

                TextColumn::make('last_login')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('role')
                    ->options(Role::class),
                SelectFilter::make('status')
                    ->options(Status::class),
            ])
            ->recordActions([
                EditAction::make(),
                DeleteAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
