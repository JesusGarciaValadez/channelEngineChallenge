<?php

namespace App\OrderLines\Models;

use App\Orders\Models\Order;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 *
 *
 * @method static updateOrCreate(array $array, array $array1)
 * @method static withSum(string $string)
 * @method static select(string $string, string $string1)
 * @property mixed $id
 * @property mixed $merchant_product_no
 * @property mixed $stock_location_id
 * @property int $order_id
 * @property string|null $gtin
 * @property string $description
 * @property int $quantity
 * @property int $channel_product_no
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read Order $order
 * @method static \Illuminate\Database\Eloquent\Builder<static>|OrderLine newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|OrderLine newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|OrderLine query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|OrderLine whereChannelProductNo($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|OrderLine whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|OrderLine whereDescription($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|OrderLine whereGtin($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|OrderLine whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|OrderLine whereMerchantProductNo($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|OrderLine whereOrderId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|OrderLine whereQuantity($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|OrderLine whereStockLocationId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|OrderLine whereUpdatedAt($value)
 * @mixin \Eloquent
 */
class OrderLine extends Model
{
    protected $fillable = [
        'order_id',
        'merchant_product_no',
        'gtin',
        'description',
        'quantity',
        'merchant_product_no',
        'channel_product_no',
        'stock_location_id',
    ];

    public function getRouteKeyName(): string
    {
        return 'merchant_product_no';
    }

    /**
     * @return BelongsTo
     */
    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }
}
