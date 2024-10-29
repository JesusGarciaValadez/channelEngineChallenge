<?php

namespace App\OrderLines\Services;

use App\OrderLines\Models\OrderLine;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Http;

class OrderLineService
{
    private string $apiUrl;
    private string $apiKey;

    public function __construct()
    {
        $this->apiUrl = config('services.channelengine.api_url');
        $this->apiKey = config('services.channelengine.api_key');
    }

    /**
     * @param OrderLine $orderLine
     * @return int
     * @throws ConnectionException
     */
    public function getProductStock(OrderLine $orderLine): int
    {
        try{
            $response = Http::withQueryParameters([
                'apikey' => $this->apiKey,
                'stockLocationIds' => $orderLine->stock_location_id,
            ])
                ->get("{$this->apiUrl}/v2/offer/stock");
        } catch (ConnectionException $e) {
            return 0;
        }

        return Collection::make($response->json()['Content'])
            ->filter(function ($stockLocation) use ($orderLine) {
                return $stockLocation['MerchantProductNo'] === $orderLine->merchant_product_no;
            })
            ->first()['Stock'] ?? 0;
    }

    /**
     * @param OrderLine $orderLine
     * @param int $stock
     * @return bool
     * @throws ConnectionException
     */
    public function updateProductStock(OrderLine $orderLine, int $stock): bool
    {
        $currentStock = $this->getProductStock($orderLine);

        try {
            $bodyRequest = [
                "MerchantProductNo" => $orderLine->merchant_product_no,
                "StockLocations" => [
                    [
                        "Stock" => $currentStock + $stock,
                        "StockLocationId" => $orderLine->stock_location_id
                    ]
                ]
            ];
            $response = Http::withQueryParameters(['apikey' => $this->apiKey])
                ->put("{$this->apiUrl}/v2/offer/stock", [$bodyRequest]);
        } catch (ConnectionException $e) {
            return false;
        }

        return $response->successful();
    }
}
