<?php

namespace App\Orders\Services;

use App\OrderLines\Models\OrderLine;
use App\Orders\Models\Order;
use Exception;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;

class OrderService
{
    private string $apiUrl;
    private string $apiKey;

    public function __construct()
    {
        $this->apiUrl = config('services.channelengine.api_url');
        $this->apiKey = config('services.channelengine.api_key');
    }

    /**
     * @throws Exception
     */
    public function getOrdersInProgress()
    {
        $response = Http::get("{$this->apiUrl}/v2/orders", [
            'apikey' => $this->apiKey,
            'statuses' => 'IN_PROGRESS',
        ]);

        if ($this->responseIsSuccessful($response)) {
            $orders = $response->json()['Content'] ?? [];

            foreach($orders as $orderData) {
                $order = Order::updateOrCreate(
                    ['channel_order_no' => $orderData['ChannelOrderNo']],
                    ['status' => $orderData['Status']]
                );

                foreach($orderData['Lines'] as $lineData) {
                    OrderLine::updateOrCreate(
                        [
                            'order_id' => $order->id,
                            'gtin' => $lineData['Gtin'],
                            'description' => $lineData['Description'],
                        ],
                        [
                            'quantity' => $lineData['Quantity'],
                            'merchant_product_no' => $lineData['MerchantProductNo'],
                            'channel_product_no' => $lineData['ChannelProductNo'],
                            'stock_location_id' => $lineData['StockLocation']['Id'],
                        ]
                    );
                }
            }

            return $orders;
        }

        throw new Exception('Failed to fetch orders in progress.');
    }

    private function responseIsSuccessful(Response $response): bool
    {
        if ($response->failed()) {
            return false;
        }

        if (!isset($response->json()['Content'])) {
            return false;
        }

        if (!is_array($response->json()['Content'])) {
            return false;
        }

        if (empty($response->json()['Content'])) {
            return false;
        }

        return true;
    }
}
