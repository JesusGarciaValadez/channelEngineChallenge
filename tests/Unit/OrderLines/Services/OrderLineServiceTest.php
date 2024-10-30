<?php

namespace Tests\Unit\OrderLines\Services;

use App\OrderLines\Models\OrderLine;
use App\OrderLines\Services\OrderLineService;
use App\Orders\Models\Order;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class OrderLineServiceTest extends TestCase
{
    use RefreshDatabase;

    private string $apiUrl;
    private string $apiKey;

    public function setUp(): void
    {
        parent::setUp();

        $this->apiUrl = config('services.channelengine.api_url');
        $this->apiKey = config('services.channelengine.api_key');
    }

    #[Test]
    public function it_returns_correct_product_stock(): void
    {
        // Arrange: Create an Order
        $order = Order::create([
            'channel_order_no' => 'ORD12345',
            'status'           => 'IN_PROGRESS',
        ]);

        // Create an OrderLine
        $orderLine = OrderLine::create([
            'order_id'            => $order->id,
            'gtin'                => '1234567890123', // Can be null
            'description'         => 'Product 1',
            'quantity'            => 2,
            'merchant_product_no' => 'MPN123',
            'channel_product_no'  => 1001,
            'stock_location_id'   => 1,
        ]);

        // Mock the HTTP response
        Http::fake([
            '*' => Http::response([
                'Content' => [
                    [
                        'MerchantProductNo' => 'MPN123',
                        'Stock'             => 10,
                    ],
                ],
            ], 200),
        ]);

        // Act: Call the service method
        $service = new OrderLineService();
        $stock   = $service->getProductStock($orderLine->merchant_product_no, $orderLine->stock_location_id);

        // Assert
        $this->assertEquals(10, $stock);
    }

    #[Test]
    public function it_returns_zero_when_product_not_found_in_stock(): void
    {
        // Arrange: Create an Order
        $order = Order::create([
            'channel_order_no' => 'ORD67890',
            'status'           => 'COMPLETED',
        ]);

        // Create an OrderLine with a MerchantProductNo that will not be found
        $orderLine = OrderLine::create([
            'order_id'            => $order->id,
            'gtin'                => '9876543210987',
            'description'         => 'Product Not Found',
            'quantity'            => 1,
            'merchant_product_no' => 'MPN_NOT_FOUND',
            'channel_product_no'  => 1002,
            'stock_location_id'   => 2,
        ]);

        // Mock the HTTP response with different product data
        Http::fake([
            '*' => Http::response([
                'Content' => [
                    [
                        'MerchantProductNo' => 'MPN_OTHER',
                        'Stock'             => 5,
                    ],
                ],
            ], 200),
        ]);

        // Act
        $service = new OrderLineService();
        $stock   = $service->getProductStock($orderLine->merchant_product_no, $orderLine->stock_location_id);

        // Assert
        $this->assertEquals(0, $stock);
    }

    #[Test]
    public function it_throws_an_exception_when_http_response_has_no_content(): void
    {
        // Assert: Expect an exception due to invalid data format
        $this->expectException(ConnectionException::class);

        // Arrange: Create an Order and OrderLine
        $order = Order::create([
            'channel_order_no' => 'ORD99999',
            'status'           => 'PENDING',
        ]);

        $orderLine = OrderLine::create([
            'order_id'            => $order->id,
            'description'         => 'Product with No Content',
            'quantity'            => 3,
            'merchant_product_no' => 'MPN_NO_CONTENT',
            'channel_product_no'  => 9999,
            'stock_location_id'   => 9,
        ]);

        // Mock the HTTP response with empty content
        Http::fake([
            '*' => Http::response([], 200),
        ]);

        // Act
        $service = new OrderLineService();
        $stock   = $service->getProductStock($orderLine->merchant_product_no, $orderLine->stock_location_id);
    }

    #[Test]
    public function it_returns_zero_when_http_request_fails(): void
    {
        // Assert: Expect an exception due to invalid data format
        $this->expectException(ConnectionException::class);

        // Arrange: Create an Order and OrderLine
        $order = Order::create([
            'channel_order_no' => 'ORD88888',
            'status'           => 'FAILED',
        ]);

        $orderLine = OrderLine::create([
            'order_id'            => $order->id,
            'description'         => 'Product with Connection Issue',
            'quantity'            => 4,
            'merchant_product_no' => 'MPN_CONN_ISSUE',
            'channel_product_no'  => 8888,
            'stock_location_id'   => 8,
        ]);

        // Mock the HTTP request to throw a ConnectionException
        Http::fake([
            '*' => Http::sequence()->push(function () {
                throw new ConnectionException();
            }),
        ]);

        // Act
        $service = new OrderLineService();
        $stock   = $service->getProductStock($orderLine->merchant_product_no, $orderLine->stock_location_id);
    }

    #[Test]
    public function it_updates_product_stock_successfully(): void
    {
        // Arrange: Create an Order and OrderLine
        $order = Order::create([
            'channel_order_no' => 'ORD55555',
            'status'           => 'SHIPPED',
        ]);

        $orderLine = OrderLine::create([
            'order_id'            => $order->id,
            'description'         => 'Product to Update Stock',
            'quantity'            => 5,
            'merchant_product_no' => 'MPN_UPDATE_STOCK',
            'channel_product_no'  => 5555,
            'stock_location_id'   => 5,
        ]);

        // Mock getProductStock to return current stock of 10
        Http::fake([
            '*' => Http::sequence()
                ->push([
                    'Content' => [
                        [
                            'MerchantProductNo' => 'MPN_UPDATE_STOCK',
                            'Stock'             => 10,
                        ],
                    ],
                ], 200) // First call for getProductStock
                ->whenEmpty(function () {
                    // Second call for updateProductStock
                    return Http::response([], 200);
                }),
        ]);

        // Act
        $service = new OrderLineService();
        $result  = $service->updateProductStock($orderLine->merchant_product_no, $orderLine->stock_location_id, 5);

        // Assert
        $this->assertTrue($result);

        // Ensure that the correct data was sent in the HTTP request
        Http::assertSent(function ($request) use ($orderLine) {
            if ($request->url() !== "{$this->apiUrl}/v2/offer/stock?apikey={$this->apiKey}") {
                return false;
            }

            $body = $request->data();
            $expectedBody = [
                [
                    'MerchantProductNo' => 'MPN_UPDATE_STOCK',
                    'StockLocations'    => [
                        [
                            'Stock'           => 15, // 10 existing + 5 added
                            'StockLocationId' => 5,
                        ],
                    ],
                ],
            ];

            return $body == $expectedBody;
        });
    }

    #[Test]
    public function it_returns_false_when_update_product_stock_fails(): void
    {
        // Arrange: Create an Order and OrderLine
        $order = Order::create([
            'channel_order_no' => 'ORD44444',
            'status'           => 'CANCELLED',
        ]);

        $orderLine = OrderLine::create([
            'order_id'            => $order->id,
            'description'         => 'Product Update Fail',
            'quantity'            => 2,
            'merchant_product_no' => 'MPN_UPDATE_FAIL',
            'channel_product_no'  => 4444,
            'stock_location_id'   => 4,
        ]);

        // Mock getProductStock to return current stock of 5
        Http::fake([
            '*' => Http::sequence()
                ->push([
                    'Content' => [
                        [
                            'MerchantProductNo' => 'MPN_UPDATE_FAIL',
                            'Stock'             => 5,
                        ],
                    ],
                ], 200) // First call for getProductStock
                ->push([], 500), // Second call for updateProductStock (failure)
        ]);

        // Act
        $service = new OrderLineService();
        $result  = $service->updateProductStock($orderLine->merchant_product_no, $orderLine->stock_location_id, 3);

        // Assert
        $this->assertFalse($result);
    }

    #[Test]
    public function it_throws_an_exception_when_update_product_stock_connection_exception(): void
    {
        // Assert: Expect an exception due to invalid data format
        $this->expectException(ConnectionException::class);

        // Arrange: Create an Order and OrderLine
        $order = Order::create([
            'channel_order_no' => 'ORD33333',
            'status'           => 'PROCESSING',
        ]);

        $orderLine = OrderLine::create([
            'order_id'            => $order->id,
            'description'         => 'Product Connection Exception',
            'quantity'            => 1,
            'merchant_product_no' => 'MPN_CONN_EXCEPTION',
            'channel_product_no'  => 3333,
            'stock_location_id'   => 3,
        ]);

        // Mock getProductStock to throw a ConnectionException
        Http::fake([
            '*' => Http::sequence()->push(function () {
                throw new ConnectionException();
            }),
        ]);

        // Act
        $service = new OrderLineService();
        $result  = $service->updateProductStock($orderLine->merchant_product_no, $orderLine->stock_location_id, 2);

        // Assert
        $this->assertFalse($result);
    }

    #[Test]
    public function it_handles_invalid_json_response_in_get_product_stock(): void
    {
        // Assert: Expect an exception due to invalid data format
        $this->expectException(ConnectionException::class);

        // Arrange: Create an Order and OrderLine
        $order = Order::create([
            'channel_order_no' => 'ORD22222',
            'status'           => 'IN_PROGRESS',
        ]);

        $orderLine = OrderLine::create([
            'order_id'            => $order->id,
            'description'         => 'Product Invalid JSON',
            'quantity'            => 2,
            'merchant_product_no' => 'MPN_INVALID_JSON',
            'channel_product_no'  => 2222,
            'stock_location_id'   => 2,
        ]);

        // Mock the HTTP response with invalid JSON
        Http::fake([
            '*' => Http::response('Invalid JSON', 200),
        ]);

        // Act
        $service = new OrderLineService();
        $stock   = $service->getProductStock($orderLine->merchant_product_no, $orderLine->stock_location_id);

        // Assert
        $this->assertEquals(0, $stock);
    }

    #[Test]
    public function it_handles_missing_content_key_in_get_product_stock(): void
    {
        // Assert: Expect an exception due to invalid data format
        $this->expectException(ConnectionException::class);

        // Arrange: Create an Order and OrderLine
        $order = Order::create([
            'channel_order_no' => 'ORD11111',
            'status'           => 'NEW',
        ]);

        $orderLine = OrderLine::create([
            'order_id'            => $order->id,
            'description'         => 'Product Missing Content',
            'quantity'            => 3,
            'merchant_product_no' => 'MPN_MISSING_CONTENT',
            'channel_product_no'  => 1111,
            'stock_location_id'   => 1,
        ]);

        // Mock the HTTP response missing 'Content' key
        Http::fake([
            '*' => Http::response([
                'NoContentKey' => [],
            ], 200),
        ]);

        // Act
        $service = new OrderLineService();
        $stock   = $service->getProductStock($orderLine->merchant_product_no, $orderLine->stock_location_id);

        // Assert
        $this->assertEquals(0, $stock);
    }

    #[Test]
    public function it_handles_null_stock_in_get_product_stock(): void
    {
        // Arrange: Create an Order and OrderLine
        $order = Order::create([
            'channel_order_no' => 'ORD66666',
            'status'           => 'PENDING',
        ]);

        $orderLine = OrderLine::create([
            'order_id'            => $order->id,
            'description'         => 'Product Null Stock',
            'quantity'            => 2,
            'merchant_product_no' => 'MPN_NULL_STOCK',
            'channel_product_no'  => 6666,
            'stock_location_id'   => 6,
        ]);

        // Mock the HTTP response with null 'Stock'
        Http::fake([
            '*' => Http::response([
                'Content' => [
                    [
                        'MerchantProductNo' => 'MPN_NULL_STOCK',
                        'Stock'             => null,
                    ],
                ],
            ], 200),
        ]);

        // Act
        $service = new OrderLineService();
        $stock   = $service->getProductStock($orderLine->merchant_product_no, $orderLine->stock_location_id);

        // Assert
        $this->assertEquals(0, $stock);
    }

    #[Test]
    public function it_handles_negative_stock_in_get_product_stock(): void
    {
        // Arrange: Create an Order and OrderLine
        $order = Order::create([
            'channel_order_no' => 'ORD77777',
            'status'           => 'ON_HOLD',
        ]);

        $orderLine = OrderLine::create([
            'order_id'            => $order->id,
            'description'         => 'Product Negative Stock',
            'quantity'            => 1,
            'merchant_product_no' => 'MPN_NEGATIVE_STOCK',
            'channel_product_no'  => 7777,
            'stock_location_id'   => 7,
        ]);

        // Mock the HTTP response with negative 'Stock'
        Http::fake([
            '*' => Http::response([
                'Content' => [
                    [
                        'MerchantProductNo' => 'MPN_NEGATIVE_STOCK',
                        'Stock'             => -5,
                    ],
                ],
            ], 200),
        ]);

        // Act
        $service = new OrderLineService();
        $stock   = $service->getProductStock($orderLine->merchant_product_no, $orderLine->stock_location_id);

        // Assert
        $this->assertEquals(-5, $stock);
    }

    #[Test]
    public function it_updates_product_stock_even_with_zero_current_stock(): void
    {
        // Arrange: Create an Order and OrderLine
        $order = Order::create([
            'channel_order_no' => 'ORD00000',
            'status'           => 'COMPLETED',
        ]);

        $orderLine = OrderLine::create([
            'order_id'            => $order->id,
            'description'         => 'Product Zero Stock',
            'quantity'            => 4,
            'merchant_product_no' => 'MPN_ZERO_STOCK',
            'channel_product_no'  => 0,
            'stock_location_id'   => 0,
        ]);

        // Mock getProductStock to return zero
        Http::fake([
            '*' => Http::sequence()
                ->push([
                    'Content' => [], // Empty content, so stock will be zero
                ], 200)
                ->whenEmpty(function () {
                    // Second call for updateProductStock
                    return Http::response([], 200);
                }),
        ]);

        // Act
        $service = new OrderLineService();
        $result  = $service->updateProductStock($orderLine->merchant_product_no, $orderLine->stock_location_id, 5);

        // Assert
        $this->assertTrue($result);

        // Ensure correct data was sent
        Http::assertSent(function ($request) use ($orderLine) {
            if ($request->url() !== "{$this->apiUrl}/v2/offer/stock?apikey={$this->apiKey}") {
                return false;
            }

            $body = $request->data();
            $expectedBody = [
                [
                    'MerchantProductNo' => 'MPN_ZERO_STOCK',
                    'StockLocations'    => [
                        [
                            'Stock'           => 5, // 0 existing + 5 added
                            'StockLocationId' => 0,
                        ],
                    ],
                ],
            ];

            return $body == $expectedBody;
        });
    }

    #[Test]
    public function it_does_not_update_stock_when_get_product_stock_fails(): void
    {
        // Assert: Expect an exception due to invalid data format
        $this->expectException(ConnectionException::class);

        // Arrange: Create an Order and OrderLine
        $order = Order::create([
            'channel_order_no' => 'ORD99990',
            'status'           => 'FAILED',
        ]);

        $orderLine = OrderLine::create([
            'order_id'            => $order->id,
            'description'         => 'Product Stock Fail',
            'quantity'            => 2,
            'merchant_product_no' => 'MPN_STOCK_FAIL',
            'channel_product_no'  => 9990,
            'stock_location_id'   => 9,
        ]);

        // Mock getProductStock to throw an exception
        Http::fake([
            '*' => Http::sequence()->push(function () {
                throw new ConnectionException();
            }),
        ]);

        // Act
        $service = new OrderLineService();
        $result  = $service->updateProductStock($orderLine->merchant_product_no, $orderLine->stock_location_id, 3);

        // Assert
        $this->assertFalse($result);
    }
}


