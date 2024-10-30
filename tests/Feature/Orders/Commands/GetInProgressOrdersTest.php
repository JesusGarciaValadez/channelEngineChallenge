<?php

namespace Tests\Feature\Orders\Commands;

use App\Orders\Models\Order;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class GetInProgressOrdersTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function command_runs_successfully(): void
    {
        // Arrange: Mock the HTTP response
        Http::fake([
            '*' => Http::response([
                'Content' => [
                    [
                        'ChannelOrderNo' => '12345',
                        'Status'        => 'IN_PROGRESS',
                        'Lines'         => [
                            [
                                'Gtin'              => '1234567890123',
                                'Description'       => 'Product 1',
                                'Quantity'          => 2,
                                'MerchantProductNo' => 'MPN1',
                                'ChannelProductNo'  => 1001,
                                'StockLocation'     => [
                                    'Id' => 1,
                                ],
                            ],
                        ],
                    ],
                ],
            ], 200),
        ]);

        // Act: Run the command
        $this->artisan('orders:get-in-progress-orders')
            ->expectsOutput('Fetched orders successfully.')
            ->assertExitCode(0);

        // Assert: Check that orders and order lines are created
        $this->assertDatabaseHas('orders', [
            'channel_order_no' => '12345',
            'status'           => 'IN_PROGRESS',
        ]);

        $order = Order::where('channel_order_no', '12345')->first();
        $this->assertNotNull($order);

        $this->assertDatabaseHas('order_lines', [
            'order_id'            => $order->id,
            'gtin'                => '1234567890123',
            'description'         => 'Product 1',
            'quantity'            => 2,
            'merchant_product_no' => 'MPN1',
            'channel_product_no'  => 1001,
            'stock_location_id'   => 1,
        ]);
    }

    #[Test]
    public function command_handles_exception_on_failed_http_request(): void
    {
        // Arrange: Mock an unsuccessful HTTP response
        Http::fake([
            '*' => Http::response([], 500),
        ]);

        // Act: Run the command
        $this->artisan('orders:get-in-progress-orders')
            ->expectsOutput('Error: Failed to fetch orders in progress.')
            ->assertExitCode(0);

        // Assert: No orders should be created
        $this->assertDatabaseCount('orders', 0);
        $this->assertDatabaseCount('order_lines', 0);
    }

    #[Test]
    public function command_handles_empty_content(): void
    {
        // Arrange: Mock the HTTP response with empty Content
        Http::fake([
            '*' => Http::response([
                'Content' => [],
            ], 200),
        ]);

        // Act: Run the command
        $this->artisan('orders:get-in-progress-orders')
            ->expectsOutput('Error: Failed to fetch orders in progress.')
            ->assertExitCode(0);

        // Assert: No orders should be created
        $this->assertDatabaseCount('orders', 0);
        $this->assertDatabaseCount('order_lines', 0);
    }

    #[Test]
    public function command_updates_existing_orders(): void
    {
        // Arrange: Create an existing order
        $existingOrder = Order::create([
            'channel_order_no' => '12345',
            'status'           => 'NEW',
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
                                'Gtin'              => '1234567890123',
                                'Description'       => 'Product 1 Updated',
                                'Quantity'          => 3,
                                'MerchantProductNo' => 'MPN1',
                                'ChannelProductNo'  => 1001,
                                'StockLocation'     => [
                                    'Id' => 2,
                                ],
                            ],
                        ],
                    ],
                ],
            ], 200),
        ]);

        // Act: Run the command
        $this->artisan('orders:get-in-progress-orders')
            ->expectsOutput('Fetched orders successfully.')
            ->assertExitCode(0);

        // Assert: Check that the order is updated
        $this->assertDatabaseHas('orders', [
            'channel_order_no' => '12345',
            'status'           => 'IN_PROGRESS',
        ]);

        $order = Order::where('channel_order_no', '12345')->first();
        $this->assertEquals('IN_PROGRESS', $order->status);

        // Check that the order line is updated
        $this->assertDatabaseHas('order_lines', [
            'order_id'            => $order->id,
            'gtin'                => '1234567890123',
            'description'         => 'Product 1 Updated',
            'quantity'            => 3,
            'merchant_product_no' => 'MPN1',
            'channel_product_no'  => 1001,
            'stock_location_id'   => 2,
        ]);
    }

    #[Test]
    public function command_handles_invalid_json_response(): void
    {
        // Arrange: Mock the HTTP response with invalid JSON
        Http::fake([
            '*' => Http::response('Invalid JSON', 200),
        ]);

        // Act: Run the command
        $this->artisan('orders:get-in-progress-orders')
            ->expectsOutput('Error: Failed to fetch orders in progress.')
            ->assertExitCode(0);

        // Assert: No orders should be created
        $this->assertDatabaseCount('orders', 0);
        $this->assertDatabaseCount('order_lines', 0);
    }

    #[Test]
    public function command_handles_response_missing_content_key(): void
    {
        // Arrange: Mock the HTTP response missing 'Content' key
        Http::fake([
            '*' => Http::response([
                'MissingContent' => [],
            ], 200),
        ]);

        // Act: Run the command
        $this->artisan('orders:get-in-progress-orders')
            ->expectsOutput('Error: Failed to fetch orders in progress.')
            ->assertExitCode(0);

        // Assert: No orders should be created
        $this->assertDatabaseCount('orders', 0);
        $this->assertDatabaseCount('order_lines', 0);
    }

    #[Test]
    public function command_handles_order_with_no_lines(): void
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

        // Act: Run the command
        $this->artisan('orders:get-in-progress-orders')
            ->expectsOutput('Fetched orders successfully.')
            ->assertExitCode(0);

        // Assert: Order is created, but no order lines
        $this->assertDatabaseHas('orders', [
            'channel_order_no' => '12345',
            'status'           => 'IN_PROGRESS',
        ]);

        $order = Order::where('channel_order_no', '12345')->first();
        $this->assertNotNull($order);

        $this->assertDatabaseCount('order_lines', 0);
    }

    #[Test]
    public function command_handles_line_with_missing_optional_fields(): void
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
                                'ChannelProductNo'  => 1002,
                                'StockLocation'     => [
                                    'Id' => 2,
                                ],
                            ],
                        ],
                    ],
                ],
            ], 200),
        ]);

        // Act: Run the command
        $this->artisan('orders:get-in-progress-orders')
            ->expectsOutput('Fetched orders successfully.')
            ->assertExitCode(0);

        // Assert: Order line is created with nullable 'gtin'
        $order = Order::where('channel_order_no', '12345')->first();
        $this->assertNotNull($order);

        $this->assertDatabaseHas('order_lines', [
            'order_id'            => $order->id,
            'gtin'                => null,
            'description'         => 'Product without GTIN',
            'quantity'            => 1,
            'merchant_product_no' => 'MPN2',
            'channel_product_no'  => 1002,
            'stock_location_id'   => 2,
        ]);
    }

    #[Test]
    public function command_handles_multiple_orders_and_lines(): void
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
                                'ChannelProductNo'  => 1001,
                                'StockLocation'     => [
                                    'Id' => 1,
                                ],
                            ],
                            [
                                'Gtin'              => '2222222222222',
                                'Description'       => 'Product 2',
                                'Quantity'          => 3,
                                'MerchantProductNo' => 'MPN2',
                                'ChannelProductNo'  => 1002,
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
                                'ChannelProductNo'  => 1003,
                                'StockLocation'     => [
                                    'Id' => 3,
                                ],
                            ],
                        ],
                    ],
                ],
            ], 200),
        ]);

        // Act: Run the command
        $this->artisan('orders:get-in-progress-orders')
            ->expectsOutput('Fetched orders successfully.')
            ->assertExitCode(0);

        // Assert: Multiple orders and lines are created
        $this->assertDatabaseHas('orders', [
            'channel_order_no' => '12345',
            'status'           => 'IN_PROGRESS',
        ]);

        $this->assertDatabaseHas('orders', [
            'channel_order_no' => '67890',
            'status'           => 'IN_PROGRESS',
        ]);

        $order1 = Order::where('channel_order_no', '12345')->first();
        $order2 = Order::where('channel_order_no', '67890')->first();

        $this->assertDatabaseHas('order_lines', [
            'order_id'            => $order1->id,
            'gtin'                => '1111111111111',
            'description'         => 'Product 1',
            'quantity'            => 1,
            'merchant_product_no' => 'MPN1',
            'channel_product_no'  => 1001,
            'stock_location_id'   => 1,
        ]);

        $this->assertDatabaseHas('order_lines', [
            'order_id'            => $order1->id,
            'gtin'                => '2222222222222',
            'description'         => 'Product 2',
            'quantity'            => 3,
            'merchant_product_no' => 'MPN2',
            'channel_product_no'  => 1002,
            'stock_location_id'   => 2,
        ]);

        $this->assertDatabaseHas('order_lines', [
            'order_id'            => $order2->id,
            'gtin'                => '3333333333333',
            'description'         => 'Product 3',
            'quantity'            => 2,
            'merchant_product_no' => 'MPN3',
            'channel_product_no'  => 1003,
            'stock_location_id'   => 3,
        ]);
    }
}

