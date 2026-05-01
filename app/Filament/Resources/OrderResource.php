<?php

namespace App\Filament\Resources;

use App\Filament\Resources\OrderResource\Pages;
use App\Models\Order;
use App\Models\Product;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Forms\Set;
use Filament\Forms\Get;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Group;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\ToggleButtons;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Hidden;
use Illuminate\Support\Number;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\SelectColumn;
use Filament\Tables\Actions\ActionGroup;
use Filament\Tables\Filters\SelectFilter;
use App\Filament\Resources\OrderResource\RelationManagers\AddressRelationManager;

class OrderResource extends Resource
{
    protected static ?string $model = Order::class;

    protected static ?string $navigationIcon = 'heroicon-o-shopping-bag';

    protected static ?int $navigationSort = 5;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Group::make([
                    Section::make('Order Information')
                        ->schema([
                            Select::make('user_id')
                                ->label('Customer')
                                ->relationship('user', 'name')
                                ->searchable()
                                ->preload()
                                ->required(),

                            Select::make('payment_method')
                                ->options([
                                    'stripe' => 'Stripe',
                                    'cod' => 'Cash on Delivery',
                                ])
                                ->required(),

                            Select::make('payment_status')
                                ->options([
                                    'pending' => 'Pending',
                                    'paid' => 'Paid',
                                    'failed' => 'Failed',
                                ])
                                ->default('pending')
                                ->required(),

                            ToggleButtons::make('status')
                                ->inline()
                                ->default('new')
                                ->required()
                                ->options([
                                    'new' => 'New',
                                    'processing' => 'Processing',
                                    'shipped' => 'Shipped',
                                    'delivered' => 'Delivered',
                                    'cancelled' => 'Cancelled',
                                ])
                                ->colors([
                                    'new' => 'info',
                                    'processing' => 'warning',
                                    'shipped' => 'success',
                                    'delivered' => 'success',
                                    'cancelled' => 'danger',
                                ])
                                ->icons([
                                    'new' => 'heroicon-m-sparkles',
                                    'processing' => 'heroicon-m-arrow-path',
                                    'shipped' => 'heroicon-m-truck',
                                    'delivered' => 'heroicon-m-check-badge',
                                    'cancelled' => 'heroicon-m-x-circle',
                                ]),

                            Select::make('currency')
                                ->options([
                                    'AOA' => 'AOA',
                                    'USD' => 'USD',
                                    'EUR' => 'EUR',
                                ])
                                ->default('AOA')
                                ->required(),

                            Select::make('shipping_method')
                                ->options([
                                    'fedex' => 'FedEx',
                                    'usps' => 'USPS',
                                    'dhl' => 'DHL',
                                    'express' => 'Express',
                                ])
                                ->required(),

                            Textarea::make('notes')
                                ->rows(3)
                                ->columnSpanFull(),
                        ])
                        ->columns(2),

                    Section::make('Order Items')
                        ->schema([
                            Repeater::make('items')
                                ->relationship()
                                ->schema([
                                    Select::make('product_id')
                                        ->relationship('product', 'name')
                                        ->searchable()
                                        ->preload()
                                        ->required()
                                        ->distinct()
                                        ->disableOptionsWhenSelectedInSiblingRepeaterItems()
                                        ->columnSpan(4)
                                        ->reactive()
                                        ->afterStateUpdated(
                                            fn ($state, Set $set) =>
                                                $set('unit_amount', Product::find($state)?->price ?? 0)
                                        )
                                        ->afterStateUpdated(
                                            fn ($state, Set $set) =>
                                                $set('total_amount', Product::find($state)?->price ?? 0)
                                        ),

                                    TextInput::make('quantity')
                                        ->required()
                                        ->numeric()
                                        ->default(1)
                                        ->minValue(1)
                                        ->columnSpan(2)
                                        ->reactive()
                                        ->afterStateUpdated(function ($state, Set $set, Get $get) {
                                            $unitAmount = $get('unit_amount') ?? 0;
                                            $set('total_amount', $unitAmount * $state);
                                        }),

                                    TextInput::make('unit_amount')
                                        ->required()
                                        ->numeric()
                                        ->disabled()
                                        ->dehydrated()
                                        ->columnSpan(3),

                                    TextInput::make('total_amount')
                                        ->required()
                                        ->numeric()
                                        ->disabled()
                                        ->dehydrated()
                                        ->columnSpan(3),
                                ])
                                ->columns(12),

                                Placeholder::make('grand_total')
                                    ->label('Grand Total')
                                    ->content(function(Get $get, Set $set){
                                        $total = 0;
                                        if(!$repeaters = $get('items')){
                                            return $total;
                                        }

                                        foreach($repeaters as $key => $repeater){
                                            $total += $get("items.{$key}.total_amount");
                                        }

                                        $set('grand_total', $total);
                                        return Number::currency($total, $get('currency') ?? 'AOA');
                                    }),

                                Hidden::make('grand_total')
                                    ->default(0),
                        ]),
                ])
                ->columnSpanFull(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('user.name')
                    ->label('Customer')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('grand_total')
                    ->money('currency')
                    ->numeric()
                    ->sortable(),

                TextColumn::make('currency')
                    ->numeric()
                    ->sortable(),

                TextColumn::make('payment_method')
                    ->sortable()
                    ->searchable(),

                TextColumn::make('payment_status')
                    ->sortable()
                    ->searchable(),

                SelectColumn::make('status')
                    ->options([
                        'new' => 'New',
                        'processing' => 'Processing',
                        'shipped' => 'Shipped',
                        'delivered' => 'Delivered',
                        'cancelled' => 'Cancelled',
                    ])
                    ->sortable()
                    ->searchable(),

                TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                //
            ])
            ->actions([
                    ActionGroup::make([
                        Tables\Actions\ViewAction::make(),
                        Tables\Actions\EditAction::make(),
                        Tables\Actions\DeleteAction::make(),
                    ]),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            AddressRelationManager::class,
        ];
    }

    public static function getNavigationBadge(): ?string
    {
        return (string) static::getModel()::count();
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return (string) static::getModel()::count() > 10 ? 'success' : 'danger';
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListOrders::route('/'),
            'create' => Pages\CreateOrder::route('/create'),
            'view' => Pages\ViewOrder::route('/{record}'),
            'edit' => Pages\EditOrder::route('/{record}/edit'),
        ];
    }
}
