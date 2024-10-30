<?php

namespace Tests\Unit\OrderLines\Models;

use App\OrderLines\Models\OrderLine;
use App\Orders\Models\Order;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Database\QueryException;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class OrderLineTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_can_create_an_order_line(): void
    {
        // Arrange: Create an Order to associate with OrderLine
        $order = Order::create([
            'channel_order_no' => 'ORD12345',
            'status'           => 'IN_PROGRESS',
        ]);

        $orderLineData = [
            'order_id'            => $order->id,
            'gtin'                => '1234567890123',
            'description'         => 'Product 1',
            'quantity'            => 2,
            'merchant_product_no' => 'MPN1',
            'channel_product_no'  => 1001,
            'stock_location_id'   => 1,
        ];

        // Act
        $orderLine = OrderLine::create($orderLineData);

        // Assert
        $this->assertInstanceOf(OrderLine::class, $orderLine);
        $this->assertDatabaseHas('order_lines', $orderLineData);
    }

    #[Test]
    public function it_has_fillable_attributes(): void
    {
        // Arrange: Create an Order
        $order = Order::create([
            'channel_order_no' => 'ORD67890',
            'status'           => 'COMPLETED',
        ]);

        $orderLineData = [
            'order_id'            => $order->id,
            'gtin'                => '9876543210987',
            'description'         => 'Product 2',
            'quantity'            => 1,
            'merchant_product_no' => 'MPN2',
            'channel_product_no'  => 1002,
            'stock_location_id'   => 2,
            'non_fillable'        => 'This should not be mass assignable',
        ];

        // Act
        $orderLine = OrderLine::create($orderLineData);

        // Assert
        $this->assertEquals($order->id, $orderLine->order_id);
        $this->assertEquals('9876543210987', $orderLine->gtin);
        $this->assertEquals('Product 2', $orderLine->description);
        $this->assertEquals(1, $orderLine->quantity);
        $this->assertEquals('MPN2', $orderLine->merchant_product_no);
        $this->assertEquals(1002, $orderLine->channel_product_no);
        $this->assertEquals(2, $orderLine->stock_location_id);
        $this->assertFalse(isset($orderLine->non_fillable));
    }

    #[Test]
    public function it_belongs_to_an_order(): void
    {
        // Arrange: Create an Order
        $order = Order::create([
            'channel_order_no' => 'ORD12345',
            'status'           => 'IN_PROGRESS',
        ]);

        // Act: Create an OrderLine associated with the Order
        $orderLine = OrderLine::create([
            'order_id'            => $order->id,
            'gtin'                => '1234567890123',
            'description'         => 'Product 1',
            'quantity'            => 2,
            'merchant_product_no' => 'MPN1',
            'channel_product_no'  => 1001,
            'stock_location_id'   => 1,
        ]);

        // Assert
        $this->assertInstanceOf(Order::class, $orderLine->order);
        $this->assertEquals($order->id, $orderLine->order->id);
    }

    #[Test]
    public function it_throws_exception_when_creating_order_line_without_required_fields(): void
    {
        // Arrange: Create an Order
        $order = Order::create([
            'channel_order_no' => 'ORD12345',
            'status'           => 'IN_PROGRESS',
        ]);

        // Expect a QueryException due to missing required fields
        $this->expectException(QueryException::class);

        // Act: Attempt to create an OrderLine without required 'description' and 'quantity'
        OrderLine::create([
            'order_id'            => $order->id,
            'merchant_product_no' => 'MPN1',
            'channel_product_no'  => 1001,
            'stock_location_id'   => 1,
        ]);
    }

    #[Test]
    public function it_deletes_order_line_without_affecting_order(): void
    {
        // Arrange: Create an Order and an associated OrderLine
        $order = Order::create([
            'channel_order_no' => 'ORD12345',
            'status'           => 'IN_PROGRESS',
        ]);

        $orderLine = OrderLine::create([
            'order_id'            => $order->id,
            'gtin'                => '1234567890123',
            'description'         => 'Product 1',
            'quantity'            => 2,
            'merchant_product_no' => 'MPN1',
            'channel_product_no'  => 1001,
            'stock_location_id'   => 1,
        ]);

        // Act: Delete the OrderLine
        $orderLine->delete();

        // Assert: The Order still exists
        $this->assertDatabaseHas('orders', ['id' => $order->id]);
        $this->assertDatabaseMissing('order_lines', ['id' => $orderLine->id]);
    }

    #[Test]
    public function it_can_update_or_create_an_order_line(): void
    {
        // Arrange: Create an Order
        $order = Order::create([
            'channel_order_no' => 'ORD12345',
            'status'           => 'IN_PROGRESS',
        ]);

        $attributes = [
            'order_id'    => $order->id,
            'gtin'        => '1234567890123',
            'description' => 'Product 1',
        ];

        $values = [
            'quantity'            => 2,
            'merchant_product_no' => 'MPN1',
            'channel_product_no'  => 1001,
            'stock_location_id'   => 1,
        ];

        // Act: Create or update the OrderLine
        $orderLine = OrderLine::updateOrCreate($attributes, $values);

        // Assert
        $this->assertEquals(2, $orderLine->quantity);
        $this->assertDatabaseHas('order_lines', array_merge($attributes, $values));

        // Update the OrderLine
        $newValues = [
            'quantity'          => 3,
            'stock_location_id' => 2,
        ];

        $orderLine = OrderLine::updateOrCreate($attributes, $newValues);

        // Assert
        $this->assertEquals(3, $orderLine->quantity);
        $this->assertEquals(2, $orderLine->stock_location_id);
        $this->assertDatabaseHas('order_lines', array_merge($attributes, $newValues));
    }

    #[Test]
    public function it_handles_nullable_gtin_field(): void
    {
        // Arrange: Create an Order
        $order = Order::create([
            'channel_order_no' => 'ORD99999',
            'status'           => 'PENDING',
        ]);

        // Act: Create an OrderLine with null 'gtin'
        $orderLine = OrderLine::create([
            'order_id'            => $order->id,
            'gtin'                => null,
            'description'         => 'Product without GTIN',
            'quantity'            => 5,
            'merchant_product_no' => 'MPN_NO_GTIN',
            'channel_product_no'  => 9999,
            'stock_location_id'   => 9,
        ]);

        // Assert
        $this->assertNull($orderLine->gtin);
        $this->assertDatabaseHas('order_lines', [
            'id'                  => $orderLine->id,
            'gtin'                => null,
            'description'         => 'Product without GTIN',
            'quantity'            => 5,
            'merchant_product_no' => 'MPN_NO_GTIN',
            'channel_product_no'  => 9999,
            'stock_location_id'   => 9,
        ]);
    }

    #[Test]
    public function it_can_handle_duplicate_entries(): void
    {
        // Arrange: Create an Order and an OrderLine
        $order = Order::create([
            'channel_order_no' => 'ORD12345',
            'status'           => 'IN_PROGRESS',
        ]);

        $attributes = [
            'order_id'    => $order->id,
            'gtin'        => '1234567890123',
            'description' => 'Product 1',
        ];

        $values = [
            'quantity'            => 2,
            'merchant_product_no' => 'MPN1',
            'channel_product_no'  => 1001,
            'stock_location_id'   => 1,
        ];

        OrderLine::updateOrCreate($attributes, $values);

        // Act: Try to create the same OrderLine again
        $orderLine = OrderLine::updateOrCreate($attributes, $values);

        // Assert: Only one OrderLine exists
        $this->assertDatabaseCount('order_lines', 1);
        $this->assertDatabaseHas('order_lines', array_merge($attributes, $values));
    }

    #[Test]
    public function it_can_access_order_properties_via_relationship(): void
    {
        // Arrange: Create an Order and an OrderLine
        $order = Order::create([
            'channel_order_no' => 'ORD12345',
            'status'           => 'IN_PROGRESS',
        ]);

        $orderLine = OrderLine::create([
            'order_id'            => $order->id,
            'gtin'                => '1234567890123',
            'description'         => 'Product 1',
            'quantity'            => 2,
            'merchant_product_no' => 'MPN1',
            'channel_product_no'  => 1001,
            'stock_location_id'   => 1,
        ]);

        // Act: Access order properties via the OrderLine
        $orderStatus = $orderLine->order->status;

        // Assert
        $this->assertEquals('IN_PROGRESS', $orderStatus);
    }
}

