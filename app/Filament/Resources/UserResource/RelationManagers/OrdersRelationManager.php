<?php

namespace App\Filament\Resources\UserResource\RelationManagers;

use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Actions\ActionGroup;
use Filament\Tables\Actions\Action;
use App\Filament\Resources\OrderResource;
use App\Models\Order;

class OrdersRelationManager extends RelationManager
{
    protected static string $relationship = 'orders';

    public function form(Form $form): Form
    {
        return $form
            ->schema([

            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('id')
            ->columns([
                TextColumn::make('id')
                    ->label('#')
                    ->searchable(),

                TextColumn::make('grand_total')
                    ->money('AOA'),

                TextColumn::make('status')
                    ->badge()
                    ->color(fn ($state) => match ($state) {
                        'new' => 'info',
                        'processing' => 'warning',
                        'shipped' => 'success',
                        'delivered' => 'success',
                        'cancelled' => 'danger',
                        default => null,
                    })
                    ->icon(fn ($state) => match ($state) {
                        'new' => 'heroicon-m-sparkles',
                        'processing' => 'heroicon-m-arrow-path',
                        'shipped' => 'heroicon-m-truck',
                        'delivered' => 'heroicon-m-check',
                        'cancelled' => 'heroicon-m-x-circle',
                        default => null,
                    })
                    ->sortable(),

                TextColumn::make('payment_method')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('payment_status')
                    ->badge()
                    ->color(fn ($state) => match ($state) {
                        'pending' => 'warning',
                        'paid' => 'success',
                        'failed' => 'danger',
                        default => null,
                    })
                    ->icon(fn ($state) => match ($state) {
                        'pending' => 'heroicon-m-clock',
                        'paid' => 'heroicon-m-check',
                        'failed' => 'heroicon-m-x-circle',
                        default => null,
                    })
                    ->searchable()
                    ->sortable(),

                TextColumn::make('created_at')
                    ->label('Order Date')
                    ->dateTime()
                    ->sortable(),

            ])
            ->filters([
                //
            ])
            ->headerActions([

            ])
            ->actions([
                ActionGroup::make([
                    Action::make('View Order')
                        ->url(fn (Order $record): string => OrderResource::getUrl('view', ['record' => $record]))
                        ->color('info')
                        ->icon('heroicon-m-eye'),


                    Tables\Actions\DeleteAction::make(),
                ]),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }
}
