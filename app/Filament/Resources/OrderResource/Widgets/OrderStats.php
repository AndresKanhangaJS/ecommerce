<?php

namespace App\Filament\Resources\OrderResource\Widgets;

use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use App\Models\Order;
use Illuminate\Support\Number;

class OrderStats extends StatsOverviewWidget
{
    protected function getStats(): array
    {
        return [
            Stat::make('New Orders', Order::query()->where('status', 'new')->count()),
            Stat::make('Processing Orders', Order::query()->where('status', 'processing')->count()),
            Stat::make('Shipped Orders', Order::query()->where('status', 'shipped')->count()),
            Stat::make('Average Price', Number::currency(Order::query()->avg('grand_total'), 'AOA')),
        ];
    }
}
