<?php

namespace Tests\Feature\OrderLines\Http\Controllers;

use App\OrderLines\Models\OrderLine;
use App\OrderLines\Repositories\OrderLineRepository;
use App\OrderLines\Services\OrderLineService;
use App\Orders\Models\Order;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\ConnectionException;
use Mockery;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class OrderLineControllerTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function index_displays_top_products(): void
    {
        // Arrange: Create some OrderLines
        $order = Order::create([
            'channel_order_no' => 'ORD12345',
            'status'           => 'COMPLETED',
        ]);

        OrderLine::create([
            'order_id'            => $order->id,
            'gtin'                => '111',
            'description'         => 'Product 1',
            'quantity'            => 5,
            'merchant_product_no' => 'MPN1',
            'channel_product_no'  => 1001,
            'stock_location_id'   => 1,
        ]);

        OrderLine::create([
            'order_id'            => $order->id,
            'gtin'                => '222',
            'description'         => 'Product 2',
            'quantity'            => 10,
            'merchant_product_no' => 'MPN2',
            'channel_product_no'  => 1002,
            'stock_location_id'   => 2,
        ]);

        // Act: Call the index route
        $response = $this->get(route('order_lines.index'));

        // Assert
        $response->assertStatus(200);
        $response->assertViewIs('orders.index');
        $response->assertViewHas('topProducts');

        $topProducts = $response->viewData('topProducts');
        $this->assertCount(2, $topProducts);

        $productDescriptions = $topProducts->pluck('description')->all();
        $this->assertContains('Product 1', $productDescriptions);
        $this->assertContains('Product 2', $productDescriptions);
    }

    #[Test]
    public function index_displays_no_products_when_none_exist(): void
    {
        // Act: Call the index route
        $response = $this->get(route('order_lines.index'));

        // Assert
        $response->assertStatus(200);
        $response->assertViewIs('orders.index');
        $response->assertViewHas('topProducts');

        $topProducts = $response->viewData('topProducts');
        $this->assertCount(0, $topProducts);
    }

    #[Test]
    public function update_product_stock_success(): void
    {
        // Arrange: Create an Order and OrderLine
        $order = Order::create([
            'channel_order_no' => 'ORD67890',
            'status'           => 'IN_PROGRESS',
        ]);

        $orderLine = OrderLine::create([
            'order_id'            => $order->id,
            'gtin'                => '333',
            'description'         => 'Product 3',
            'quantity'            => 2,
            'merchant_product_no' => 'MPN3',
            'channel_product_no'  => 1003,
            'stock_location_id'   => 3,
        ]);

        // Mock the OrderLineService
        $orderLineServiceMock = Mockery::mock(OrderLineService::class);
        $orderLineServiceMock->shouldReceive('updateProductStock')
            ->once()
            ->with($orderLine->merchant_product_no, $orderLine->stock_location_id, 25)
            ->andReturn(true);

        $this->app->instance(OrderLineService::class, $orderLineServiceMock);

        // Act: Call the update route
        $response = $this->from(route('order_lines.index'))
            ->put(route('order_lines.update', $orderLine->merchant_product_no));

        // Assert
        $response->assertRedirect(route('order_lines.index'));
        $response->assertSessionHas('status', 'success');
        $response->assertSessionHas('status_message', 'Stock updated successfully');
    }

    #[Test]
    public function update_product_stock_failure(): void
    {
        // Arrange: Create an Order and OrderLine
        $order = Order::create([
            'channel_order_no' => 'ORD99999',
            'status'           => 'FAILED',
        ]);

        $orderLine = OrderLine::create([
            'order_id'            => $order->id,
            'gtin'                => '444',
            'description'         => 'Product 4',
            'quantity'            => 1,
            'merchant_product_no' => 'MPN4',
            'channel_product_no'  => 1004,
            'stock_location_id'   => 4,
        ]);

        // Mock the OrderLineService
        $orderLineServiceMock = Mockery::mock(OrderLineService::class);
        $orderLineServiceMock->shouldReceive('updateProductStock')
            ->once()
            ->with($orderLine->merchant_product_no, $orderLine->stock_location_id, 25)
            ->andReturn(false);

        $this->app->instance(OrderLineService::class, $orderLineServiceMock);

        // Act: Call the update route
        $response = $this->from(route('order_lines.index'))
            ->put(route('order_lines.update', $orderLine->merchant_product_no));

        // Assert
        $response->assertRedirect(route('order_lines.index'));
        $response->assertSessionHas('status', 'error');
        $response->assertSessionHas('status_message', 'Failed to update stock');
    }

    #[Test]
    public function update_product_stock_handles_connection_exception(): void
    {
        // Arrange: Create an Order and OrderLine
        $order = Order::create([
            'channel_order_no' => 'ORD55555',
            'status'           => 'PENDING',
        ]);

        $orderLine = OrderLine::create([
            'order_id'            => $order->id,
            'gtin'                => '555',
            'description'         => 'Product 5',
            'quantity'            => 3,
            'merchant_product_no' => 'MPN5',
            'channel_product_no'  => 1005,
            'stock_location_id'   => 5,
        ]);

        // Mock the OrderLineService
        $orderLineServiceMock = Mockery::mock(OrderLineService::class);
        $orderLineServiceMock->shouldReceive('updateProductStock')
            ->once()
            ->with($orderLine->merchant_product_no, $orderLine->stock_location_id, 25)
            ->andThrow(new ConnectionException('Connection failed'));

        $this->app->instance(OrderLineService::class, $orderLineServiceMock);

        // Act: Call the update route
        $response = $this->from(route('order_lines.index'))
            ->put(route('order_lines.update', $orderLine->merchant_product_no));

        // Assert
        $response->assertRedirect(route('order_lines.index'));
        $response->assertSessionHas('status', 'error');
        $response->assertSessionHas('status_message', 'Failed to update stock: Connection failed');
    }

    #[Test]
    public function update_product_stock_order_line_not_found(): void
    {
        // Arrange: Non-existent MerchantProductNo
        $merchantProductNo = 'MPN_NON_EXISTENT';

        // Act: Call the update route
        $response = $this->from(route('order_lines.index'))
            ->put(route('order_lines.update', $merchantProductNo));

        // Assert
        $response->assertNotFound();
    }

    #[Test]
    public function index_displays_error_when_repository_fails(): void
    {
        // Arrange: Mock the OrderLineRepository to throw an exception
        $orderLineRepositoryMock = Mockery::mock(OrderLineRepository::class);
        $orderLineRepositoryMock->shouldReceive('getTopFiveSellingProducts')
            ->once()
            ->andThrow(new \Exception('Database error'));

        $this->app->instance(OrderLineRepository::class, $orderLineRepositoryMock);

        // Act: Call the index route
        $response = $this->get(route('order_lines.index'));

        // Assert
        $response->assertStatus(500);
    }
}
