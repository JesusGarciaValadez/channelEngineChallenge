<?php

namespace Tests\Unit\Orders\Models;

use App\OrderLines\Models\OrderLine;
use App\Orders\Models\Order;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Database\QueryException;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class OrderTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_can_create_an_order(): void
    {
        // Arrange
        $orderData = [
            'channel_order_no' => 'ORD12345',
            'status'           => 'IN_PROGRESS',
        ];

        // Act
        $order = Order::create($orderData);

        // Assert
        $this->assertInstanceOf(Order::class, $order);
        $this->assertDatabaseHas('orders', $orderData);
    }

    #[Test]
    public function it_has_fillable_attributes(): void
    {
        // Arrange
        $orderData = [
            'channel_order_no' => 'ORD67890',
            'status'           => 'COMPLETED',
            'non_fillable'     => 'This should not be mass assignable',
        ];

        // Act
        $order = Order::create($orderData);

        // Assert
        $this->assertEquals('ORD67890', $order->channel_order_no);
        $this->assertEquals('COMPLETED', $order->status);
        $this->assertFalse(isset($order->non_fillable));
    }

    #[Test]
    public function it_can_have_many_order_lines(): void
    {
        // Arrange
        $order = Order::create([
            'channel_order_no' => 'ORD12345',
            'status'           => 'IN_PROGRESS',
        ]);

        $orderLine1 = OrderLine::create([
            'order_id'            => $order->id,
            'gtin'                => '1234567890123',
            'description'         => 'Product 1',
            'quantity'            => 1,
            'merchant_product_no' => 'MPN1',
            'channel_product_no'  => 1001,
            'stock_location_id'   => 1,
        ]);

        $orderLine2 = OrderLine::create([
            'order_id'            => $order->id,
            'gtin'                => '9876543210987',
            'description'         => 'Product 2',
            'quantity'            => 2,
            'merchant_product_no' => 'MPN2',
            'channel_product_no'  => 1002,
            'stock_location_id'   => 2,
        ]);

        // Act
        $lines = $order->lines;

        // Assert
        $this->assertCount(2, $lines);
        $this->assertTrue($lines->contains($orderLine1));
        $this->assertTrue($lines->contains($orderLine2));
    }

    #[Test]
    public function it_can_update_or_create_an_order(): void
    {
        // Arrange
        $attributes = ['channel_order_no' => 'ORD12345'];
        $values = ['status' => 'IN_PROGRESS'];

        // Act
        $order = Order::updateOrCreate($attributes, $values);

        // Assert
        $this->assertEquals('IN_PROGRESS', $order->status);
        $this->assertDatabaseHas('orders', array_merge($attributes, $values));

        // Update the order
        $newValues = ['status' => 'COMPLETED'];
        $order = Order::updateOrCreate($attributes, $newValues);

        // Assert
        $this->assertEquals('COMPLETED', $order->status);
        $this->assertDatabaseHas('orders', array_merge($attributes, $newValues));
    }

    #[Test]
    public function it_throws_exception_when_creating_order_without_required_fields(): void
    {
        // Arrange
        $this->expectException(QueryException::class);

        // Act
        Order::create([
            'status' => 'IN_PROGRESS',
        ]);

        // Assert handled by exception expectation
    }

    #[Test]
    public function it_handles_empty_lines_relationship(): void
    {
        // Arrange
        $order = Order::create([
            'channel_order_no' => 'ORD12345',
            'status'           => 'IN_PROGRESS',
        ]);

        // Act
        $lines = $order->lines;

        // Assert
        $this->assertCount(0, $lines);
    }
}
