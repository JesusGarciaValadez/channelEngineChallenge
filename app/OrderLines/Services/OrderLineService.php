<?php

namespace App\OrderLines\Services;

use App\OrderLines\Models\OrderLine;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Http;
use Throwable;

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
     * @param string $merchant_product_no
     * @param int $stock_location_id
     * @return int
     * @throws Throwable
     */
    public function getProductStock(string $merchant_product_no, int $stock_location_id): int
    {
        try{
            $response = Http::withQueryParameters([
                'apikey' => $this->apiKey,
                'stockLocationIds' => $stock_location_id,
            ])
                ->get("{$this->apiUrl}/v2/offer/stock");
        } catch (ConnectionException $e) {
            return 0;
        }

        throw_if(!$response->successful(), ConnectionException::class);
        throw_if(!isset($response->json()['Content']), ConnectionException::class);

        if (empty($response->json()['Content'])) {
            return 0;
        }

        return Collection::make($response->json()['Content'])
            ->filter(function ($stockLocation) use ($merchant_product_no) {
                return $stockLocation['MerchantProductNo'] === $merchant_product_no;
            })
            ->first()['Stock'] ?? 0;
    }

    /**
     * @param string $merchant_product_no
     * @param int $stock_location_id
     * @param int $stock
     * @return bool
     * @throws Throwable
     */
    public function updateProductStock(string $merchant_product_no, int $stock_location_id, int $stock): bool
    {
        $currentStock = $this->getProductStock($merchant_product_no, $stock_location_id);

        try {
            $bodyRequest = [
                "MerchantProductNo" => $merchant_product_no,
                "StockLocations" => [
                    [
                        "Stock" => $currentStock + $stock,
                        "StockLocationId" => $stock_location_id
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
