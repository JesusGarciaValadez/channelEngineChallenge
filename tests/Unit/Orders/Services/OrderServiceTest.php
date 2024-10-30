<?php

namespace Tests\Unit\Orders\Services;

use App\OrderLines\Models\OrderLine;
use App\Orders\Models\Order;
use App\Orders\Services\OrderService;
use Exception;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class OrderServiceTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function get_orders_in_progress_successful(): void
    {
        // Arrange: Mock the HTTP response
        Http::fake([
            '*' => Http::response([
                'Content' => [
                    [
                        'ChannelOrderNo' => '12345',
                        'Status' => 'IN_PROGRESS',
                        'Lines' => [
                            [
                                'Gtin' => '1234567890123',
                                'Description' => 'Product 1',
                                'Quantity' => 2,
                                'MerchantProductNo' => 'MPN1',
                                'ChannelProductNo' => 20231,
                                'StockLocation' => [
                                    'Id' => 1,
                                ],
                            ],
                        ],
                    ],
                ],
            ], 200),
        ]);

        // Act: Call the service method
        $service = new OrderService();
        $orders = $service->getOrdersInProgress();

        // Assert: Check that orders and order lines are created
        $this->assertCount(1, $orders);

        $order = Order::where('channel_order_no', '12345')->first();
        $this->assertNotNull($order);
        $this->assertEquals('IN_PROGRESS', $order->status);

        $orderLines = OrderLine::where('order_id', $order->id)->get();
        $this->assertCount(1, $orderLines);

        $orderLine = $orderLines->first();
        $this->assertEquals('1234567890123', $orderLine->gtin);
        $this->assertEquals('Product 1', $orderLine->description);
        $this->assertEquals(2, $orderLine->quantity);
        $this->assertEquals('MPN1', $orderLine->merchant_product_no);
        $this->assertEquals(20231, $orderLine->channel_product_no);
        $this->assertEquals(1, $orderLine->stock_location_id);
    }

    #[Test]
    public function get_orders_in_progress_unsuccessful(): void
    {
        // Arrange: Mock an unsuccessful HTTP response
        Http::fake([
            '*' => Http::response([], 500),
        ]);

        // Act & Assert: Expect an exception to be thrown
        $this->expectException(Exception::class);
        $this->expectExceptionMessage('Failed to fetch orders in progress.');

        $service = new OrderService();
        $service->getOrdersInProgress();
    }

    #[Test]
    public function get_orders_in_progress_empty_content()
    {
        // Arrange: Mock the HTTP response with empty Content
        Http::fake([
            '*' => Http::response([
                'Content' => [],
            ], 200),
        ]);

        // Act & Assert: Expect an exception due to invalid JSON
        $this->expectException(Exception::class);
        $this->expectExceptionMessage('Failed to fetch orders in progress.');

        // Act: Call the service method
        $service = new OrderService();
        $orders  = $service->getOrdersInProgress();
    }

    #[Test]
    public function get_orders_in_progress_order_with_no_lines()
    {
        // Arrange: Mock the HTTP response with an order having no Lines
        Http::fake([
            '*' => Http::response([
                'Content' => [
                    [
                        'ChannelOrderNo' => '12345',
                        'Status'        => 'IN_PROGRESS',
                        'Lines'         => [],
                    ],
                ],
            ], 200),
        ]);

        // Act: Call the service method
        $service = new OrderService();
        $orders  = $service->getOrdersInProgress();

        // Assert: Order is created, but no order lines
        $this->assertCount(1, $orders);

        $order = Order::where('channel_order_no', '12345')->first();
        $this->assertNotNull($order);

        $orderLines = OrderLine::where('order_id', $order->id)->get();
        $this->assertCount(0, $orderLines);
    }

    #[Test]
    public function get_orders_in_progress_line_with_missing_optional_fields()
    {
        // Arrange: Mock the HTTP response with an order line missing optional 'Gtin'
        Http::fake([
            '*' => Http::response([
                'Content' => [
                    [
                        'ChannelOrderNo' => '12345',
                        'Status'        => 'IN_PROGRESS',
                        'Lines'         => [
                            [
                                'Gtin'              => null,
                                'Description'       => 'Product without GTIN',
                                'Quantity'          => 1,
                                'MerchantProductNo' => 'MPN2',
                                'ChannelProductNo'  => 202410,
                                'StockLocation'     => [
                                    'Id' => 2,
                                ],
                            ],
                        ],
                    ],
                ],
            ], 200),
        ]);

        // Act: Call the service method
        $service = new OrderService();
        $orders  = $service->getOrdersInProgress();

        // Assert: Order line is created with nullable 'gtin'
        $this->assertCount(1, $orders);

        $order = Order::where('channel_order_no', '12345')->first();
        $this->assertNotNull($order);

        $orderLine = OrderLine::where('order_id', $order->id)->first();
        $this->assertNotNull($orderLine);
        $this->assertNull($orderLine->gtin);
        $this->assertEquals('Product without GTIN', $orderLine->description);
    }

    #[Test]
    public function get_orders_in_progress_multiple_orders_and_lines()
    {
        // Arrange: Mock the HTTP response with multiple orders and lines
        Http::fake([
            '*' => Http::response([
                'Content' => [
                    [
                        'ChannelOrderNo' => '12345',
                        'Status'        => 'IN_PROGRESS',
                        'Lines'         => [
                            [
                                'Gtin'              => '1111111111111',
                                'Description'       => 'Product 1',
                                'Quantity'          => 1,
                                'MerchantProductNo' => 'MPN1',
                                'ChannelProductNo'  => 202410,
                                'StockLocation'     => [
                                    'Id' => 1,
                                ],
                            ],
                            [
                                'Gtin'              => '2222222222222',
                                'Description'       => 'Product 2',
                                'Quantity'          => 3,
                                'MerchantProductNo' => 'MPN2',
                                'ChannelProductNo'  => 202411,
                                'StockLocation'     => [
                                    'Id' => 2,
                                ],
                            ],
                        ],
                    ],
                    [
                        'ChannelOrderNo' => '67890',
                        'Status'        => 'IN_PROGRESS',
                        'Lines'         => [
                            [
                                'Gtin'              => '3333333333333',
                                'Description'       => 'Product 3',
                                'Quantity'          => 2,
                                'MerchantProductNo' => 'MPN3',
                                'ChannelProductNo'  => 202412,
                                'StockLocation'     => [
                                    'Id' => 3,
                                ],
                            ],
                        ],
                    ],
                ],
            ], 200),
        ]);

        // Act: Call the service method
        $service = new OrderService();
        $orders  = $service->getOrdersInProgress();

        // Assert: Ensure multiple orders and lines are created
        $this->assertCount(2, $orders);

        $order1 = Order::where('channel_order_no', '12345')->first();
        $this->assertNotNull($order1);
        $orderLines1 = OrderLine::where('order_id', $order1->id)->get();
        $this->assertCount(2, $orderLines1);

        $order2 = Order::where('channel_order_no', '67890')->first();
        $this->assertNotNull($order2);
        $orderLines2 = OrderLine::where('order_id', $order2->id)->get();
        $this->assertCount(1, $orderLines2);
    }

    #[Test]
    public function get_orders_in_progress_duplicate_orders_and_lines()
    {
        // Arrange: Create existing order and line
        $existingOrder = Order::create([
            'channel_order_no' => '12345',
            'status'           => 'NEW',
        ]);

        OrderLine::create([
            'order_id'            => $existingOrder->id,
            'gtin'                => '1111111111111',
            'description'         => 'Old Product 1',
            'quantity'            => 1,
            'merchant_product_no' => 'MPN1_OLD',
            'channel_product_no'  => 202410,
            'stock_location_id'   => 1,
        ]);

        // Mock the HTTP response with updated order data
        Http::fake([
            '*' => Http::response([
                'Content' => [
                    [
                        'ChannelOrderNo' => '12345',
                        'Status'        => 'IN_PROGRESS',
                        'Lines'         => [
                            [
                                'Gtin'                => '1111111111111',
                                'Description'         => 'Old Product 1',
                                'Quantity'          => 2,
                                'MerchantProductNo' => 'MPN1',
                                'ChannelProductNo'  => 202411,
                                'StockLocation'     => [
                                    'Id' => 2,
                                ],
                            ],
                        ],
                    ],
                ],
            ], 200),
        ]);

        // Act: Call the service method
        $service = new OrderService();
        $orders  = $service->getOrdersInProgress();

        // Assert: Ensure the order and line are updated
        $order = Order::where('channel_order_no', '12345')->first();
        $this->assertEquals('IN_PROGRESS', $order->status);

        $orderLine = OrderLine::where('order_id', $order->id)
            ->where('gtin', '1111111111111')
            ->first();

        $this->assertEquals('1111111111111', $orderLine->gtin);
        $this->assertEquals('Old Product 1', $orderLine->description);
        $this->assertEquals(2, $orderLine->quantity);
        $this->assertEquals('MPN1', $orderLine->merchant_product_no);
        $this->assertEquals(202411, $orderLine->channel_product_no);
        $this->assertEquals(2, $orderLine->stock_location_id);
    }

    #[Test]
    public function get_orders_in_progress_response_missing_content_key()
    {
        // Arrange: Mock the HTTP response missing 'Content' key
        Http::fake([
            '*' => Http::response([
                'MissingContent' => [],
            ], 200),
        ]);

        // Act & Assert: Expect an exception due to invalid JSON
        $this->expectException(Exception::class);
        $this->expectExceptionMessage('Failed to fetch orders in progress.');

        // Act: Call the service method
        $service = new OrderService();
        $service->getOrdersInProgress();
    }

    #[Test]
    public function get_orders_in_progress_invalid_data_format()
    {
        // Arrange: Mock the HTTP response with invalid data format
        Http::fake([
            '*' => Http::response([
                'Content' => 'This should be an array, but it is a string',
            ], 200),
        ]);

        // Act & Assert: Expect an exception due to invalid data format
        $this->expectException(Exception::class);
        $this->expectExceptionMessage('Failed to fetch orders in progress.');

        $service = new OrderService();
        $service->getOrdersInProgress();
    }

    #[Test]
    public function get_orders_in_progress_invalid_json_response()
    {
        // Arrange: Mock the HTTP response with invalid JSON
        Http::fake([
            '*' => Http::response('Invalid JSON', 200),
        ]);

        // Act & Assert: Expect an exception due to invalid JSON
        $this->expectException(Exception::class);
        $this->expectExceptionMessage('Failed to fetch orders in progress.');

        $service = new OrderService();
        $service->getOrdersInProgress();
    }

    #[Test]
    public function get_orders_in_progress_model_creation_failure()
    {
        // Arrange: Mock the HTTP response with data that violates database constraints
        Http::fake([
            '*' => Http::response([
                'Content' => [
                    [
                        // Missing 'ChannelOrderNo' which is required in the 'Order' model
                        'Status' => 'IN_PROGRESS',
                        'Lines' => [
                            [
                                'Description' => 'Product without required fields',
                                // Missing 'Quantity' which is required in 'OrderLine' model
                                'MerchantProductNo' => 'MPN1',
                                'ChannelProductNo' => 202410,
                                'StockLocation' => [
                                    'Id' => 1,
                                ],
                            ],
                        ],
                    ],
                ],
            ], 200),
        ]);

        // Act & Assert: Expect an exception due to model creation failure
        $this->expectException(Exception::class);

        $service = new OrderService();
        $service->getOrdersInProgress();
    }
}
