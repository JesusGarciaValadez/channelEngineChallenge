<?php

namespace App\OrderLines\Repositories;

use App\OrderLines\Models\OrderLine;
use Illuminate\Database\Eloquent\Collection;

class OrderLineRepository
{
    /**
     * @return Collection
     */
    public function getTopFiveSellingProducts(): Collection
    {
        return OrderLine::select('id', 'gtin', 'description')
            ->selectRaw('SUM(quantity) as total_quantity_sold')
            ->groupBy('id', 'gtin', 'description')
            ->orderByDesc('total_quantity_sold')
            ->limit(5)
            ->get();
    }
}
