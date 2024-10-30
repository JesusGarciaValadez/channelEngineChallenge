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
        return OrderLine::select('gtin', 'description', 'merchant_product_no')
            ->selectRaw('SUM(quantity) as total_quantity_sold')
            ->groupBy('merchant_product_no', 'description', 'gtin')
            ->orderByDesc('total_quantity_sold')
            ->limit(5)
            ->get();
    }
}
