<?php

namespace Tests\Unit\OrderLines\Repositories;

use App\OrderLines\Models\OrderLine;
use App\OrderLines\Repositories\OrderLineRepository;
use App\Orders\Models\Order;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class OrderLineRepositoryTest extends TestCase
{
    use RefreshDatabase;

    private Order $orderOne;
    private Order $orderTwo;
    private Order $orderThree;

     public function setUp(): void
     {
         parent::setUp();

         $this->orderOne = Order::create([
             'channel_order_no' => '123',
             'status' => 'IN_PROGRESS',
         ]);
         $this->orderTwo = Order::create([
             'channel_order_no' => '456',
             'status' => 'IN_PROGRESS',
         ]);
         $this->orderThree = Order::create([
             'channel_order_no' => '789',
             'status' => 'IN_PROGRESS',
         ]);
     }

    #[Test]
    public function it_returns_top_five_selling_products(): void
    {
        // Arrange: Create multiple order lines with varying quantities
        OrderLine::create([
            'order_id' => $this->orderOne->id,
            'gtin' => '111',
            'description' => 'Product 1',
            'quantity' => 5,
            'merchant_product_no' => 'MPN1',
            'channel_product_no' => 1,
            'stock_location_id' => 1
        ]);
        OrderLine::create([
            'order_id' => $this->orderOne->id,
            'gtin' => '222',
            'description' => 'Product 2',
            'quantity' => 10,
            'merchant_product_no' => 'MPN2',
            'channel_product_no' => 2,
            'stock_location_id' => 2
        ]);
        OrderLine::create([
            'order_id' => $this->orderTwo->id,
            'gtin' => '333',
            'description' => 'Product 3',
            'quantity' => 15,
            'merchant_product_no' => 'MPN3',
            'channel_product_no' => 3,
            'stock_location_id' => 3
        ]);
        OrderLine::create([
            'order_id' => $this->orderTwo->id,
            'gtin' => '444',
            'description' => 'Product 4',
            'quantity' => 20,
            'merchant_product_no' => 'MPN4',
            'channel_product_no' => 4,
            'stock_location_id' => 4
        ]);
        OrderLine::create([
            'order_id' => $this->orderThree->id,
            'gtin' => '555',
            'description' => 'Product 5',
            'quantity' => 25,
            'merchant_product_no' => 'MPN5',
            'channel_product_no' => 5,
            'stock_location_id' => 5
        ]);
        OrderLine::create([
            'order_id' => $this->orderThree->id,
            'gtin' => '666',
            'description' => 'Product 6',
            'quantity' => 30,
            'merchant_product_no' => 'MPN6',
            'channel_product_no' => 6,
            'stock_location_id' => 6
        ]);

        // Act: Get top five selling products
        $repository = new OrderLineRepository();
        $topProducts = $repository->getTopFiveSellingProducts();

        // Assert: Ensure only top five products are returned, ordered by total_quantity_sold
        $this->assertCount(5, $topProducts);
        $this->assertEquals('Product 6', $topProducts->first()->description);
        $this->assertEquals(30, $topProducts->first()->total_quantity_sold);

        // Check the order of products
        $expectedOrder = ['Product 6', 'Product 5', 'Product 4', 'Product 3', 'Product 2'];
        $actualOrder = $topProducts->pluck('description')->toArray();
        $this->assertEquals($expectedOrder, $actualOrder);
    }

    #[Test]
    public function it_returns_all_products_if_less_than_five_exist(): void
    {
        // Arrange: Create fewer than five order lines
        OrderLine::create([
            'order_id' => $this->orderOne->id,
            'gtin' => '111',
            'description' => 'Product 1',
            'quantity' => 5,
            'merchant_product_no' => 'MPN1',
            'channel_product_no' => 1,
            'stock_location_id' => 1
        ]);
        OrderLine::create([
            'order_id' => $this->orderOne->id,
            'gtin' => '222',
            'description' => 'Product 2',
            'quantity' => 10,
            'merchant_product_no' => 'MPN2',
            'channel_product_no' => 2,
            'stock_location_id' => 2
        ]);

        // Act
        $repository = new OrderLineRepository();
        $topProducts = $repository->getTopFiveSellingProducts();

        // Assert
        $this->assertCount(2, $topProducts);
    }

    #[Test]
    public function it_returns_empty_collection_when_no_order_lines_exist(): void
    {
        // Arrange: No order lines in the database

        // Act
        $repository = new OrderLineRepository();
        $topProducts = $repository->getTopFiveSellingProducts();

        // Assert
        $this->assertCount(0, $topProducts);
    }

    #[Test]
    public function it_sums_quantities_for_same_product(): void
    {
        // Arrange: Multiple order lines for the same product
        OrderLine::create([
            'order_id' => $this->orderOne->id,
            'gtin' => '111',
            'description' => 'Product 1',
            'quantity' => 5,
            'merchant_product_no' => 'MPN1',
            'channel_product_no' => 1,
            'stock_location_id' => 1
        ]);
        OrderLine::create([
            'order_id' => $this->orderOne->id,
            'gtin' => '111',
            'description' => 'Product 1',
            'quantity' => 10,
            'merchant_product_no' => 'MPN1',
            'channel_product_no' => 1,
            'stock_location_id' => 1
        ]);

        // Act
        $repository = new OrderLineRepository();
        $topProducts = $repository->getTopFiveSellingProducts();

        // Assert
        $this->assertCount(1, $topProducts);
        $this->assertEquals(15, $topProducts->first()->total_quantity_sold);
    }

    #[Test]
    public function it_handles_products_with_null_gtin(): void
    {
        // Arrange: Order lines with null GTIN
        OrderLine::create([
            'order_id' => $this->orderOne->id,
            'gtin' => null,
            'description' => 'Product A',
            'quantity' => 5,
            'merchant_product_no' => 'MPN1',
            'channel_product_no' => 1,
            'stock_location_id' => 1
        ]);
        OrderLine::create([
            'order_id' => $this->orderOne->id,
            'gtin' => null,
            'description' => 'Product A',
            'quantity' => 5,
            'merchant_product_no' => 'MPN1',
            'channel_product_no' => 1,
            'stock_location_id' => 1
        ]);

        // Act
        $repository = new OrderLineRepository();
        $topProducts = $repository->getTopFiveSellingProducts();

        // Assert
        $this->assertCount(1, $topProducts);
        $this->assertEquals(10, $topProducts->first()->total_quantity_sold);
    }

    #[Test]
    public function it_excludes_order_lines_with_zero_or_negative_quantities(): void
    {
        // Arrange: Order lines with zero and negative quantities
        OrderLine::create([
            'order_id' => $this->orderOne->id,
            'gtin' => '111',
            'description' => 'Product Positive',
            'quantity' => 5,
            'merchant_product_no' => 'MPN1',
            'channel_product_no' => 1,
            'stock_location_id' => 1
        ]);
        OrderLine::create([
            'order_id' => $this->orderOne->id,
            'gtin' => '222',
            'description' => 'Product Zero',
            'quantity' => 0,
            'merchant_product_no' => 'MPN2',
            'channel_product_no' => 2,
            'stock_location_id' => 2
        ]);
        OrderLine::create([
            'order_id' => $this->orderOne->id,
            'gtin' => '333',
            'description' => 'Product Negative',
            'quantity' => -5,
            'merchant_product_no' => 'MPN3',
            'channel_product_no' => 3,
            'stock_location_id' => 3
        ]);

        // Act
        $repository = new OrderLineRepository();
        $topProducts = $repository->getTopFiveSellingProducts();

        // Assert
        $this->assertCount(3, $topProducts);

        // Quantities should reflect the actual sums
        $this->assertEquals(5, $topProducts->firstWhere('description', 'Product Positive')->total_quantity_sold);
        $this->assertEquals(0, $topProducts->firstWhere('description', 'Product Zero')->total_quantity_sold);
        $this->assertEquals(-5, $topProducts->firstWhere('description', 'Product Negative')->total_quantity_sold);
    }

    #[Test]
    public function it_handles_products_with_same_total_quantity_sold(): void
    {
        // Arrange: Products with the same total quantity sold
        OrderLine::create([
            'order_id' => $this->orderOne->id,
            'gtin' => '111',
            'description' => 'Product A',
            'quantity' => 10,
            'merchant_product_no' => 'MPN1',
            'channel_product_no' => 1,
            'stock_location_id' => 1
        ]);
        OrderLine::create([
            'order_id' => $this->orderTwo->id,
            'gtin' => '222',
            'description' => 'Product B',
            'quantity' => 10,
            'merchant_product_no' => 'MPN2',
            'channel_product_no' => 2,
            'stock_location_id' => 2
        ]);

        // Act
        $repository = new OrderLineRepository();
        $topProducts = $repository->getTopFiveSellingProducts();

        // Assert
        $this->assertCount(2, $topProducts);
        $quantities = $topProducts->pluck('total_quantity_sold')->all();
        $this->assertEquals([10, 10], $quantities);

        // Order may not be guaranteed, but both should be included
        $descriptions = $topProducts->pluck('description')->all();
        $this->assertContains('Product A', $descriptions);
        $this->assertContains('Product B', $descriptions);
    }

    #[Test]
    public function it_groups_products_by_gtin_and_description(): void
    {
        // Arrange: Products with the same GTIN but different descriptions
        OrderLine::create([
            'order_id' => $this->orderOne->id,
            'gtin' => '111',
            'description' => 'Product A',
            'quantity' => 5,
            'merchant_product_no' => 'MPN1',
            'channel_product_no' => 1,
            'stock_location_id' => 1
        ]);
        OrderLine::create([
            'order_id' => $this->orderOne->id,
            'gtin' => '111',
            'description' => 'Product A Variant',
            'quantity' => 10,
            'merchant_product_no' => 'MPN1',
            'channel_product_no' => 1,
            'stock_location_id' => 1
        ]);

        // Act
        $repository = new OrderLineRepository();
        $topProducts = $repository->getTopFiveSellingProducts();

        // Assert
        $this->assertCount(2, $topProducts);

        // Each combination should be treated separately
        $this->assertEquals(5, $topProducts->firstWhere('description', 'Product A')->total_quantity_sold);
        $this->assertEquals(10, $topProducts->firstWhere('description', 'Product A Variant')->total_quantity_sold);
    }

    #[Test]
    public function it_limits_to_five_products_even_with_more_records(): void
    {
        // Arrange: Create more than five products
        for ($i = 1; $i <= 10; $i++) {
            OrderLine::create([
                'order_id'     => $this->orderOne->id,
                'gtin'        => (string) $i,
                'description' => "Product $i",
                'quantity'    => $i * 2,
                'merchant_product_no' => "MPN$i",
                'channel_product_no' => $i,
                'stock_location_id' => $i
            ]);
        }

        // Act
        $repository = new OrderLineRepository();
        $topProducts = $repository->getTopFiveSellingProducts();

        // Assert
        $this->assertCount(5, $topProducts);

        // Top products should be those with the highest quantities
        $expectedDescriptions = ['Product 10', 'Product 9', 'Product 8', 'Product 7', 'Product 6'];
        $actualDescriptions = $topProducts->pluck('description')->all();
        $this->assertEquals($expectedDescriptions, $actualDescriptions);
    }

    #[Test]
    public function it_handles_large_quantities_correctly(): void
    {
        // Arrange: Products with large quantities
        OrderLine::create([
            'order_id' => $this->orderOne->id,
            'gtin' => '999',
            'description' => 'Product Large',
            'quantity' => 1000000,
            'merchant_product_no' => 'MPN1',
            'channel_product_no' => 1,
            'stock_location_id' => 1
        ]);

        // Act
        $repository = new OrderLineRepository();
        $topProducts = $repository->getTopFiveSellingProducts();

        // Assert
        $this->assertCount(1, $topProducts);
        $this->assertEquals(1000000, $topProducts->first()->total_quantity_sold);
    }
}

