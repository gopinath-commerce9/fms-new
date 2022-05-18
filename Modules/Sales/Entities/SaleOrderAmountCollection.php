<?php

namespace Modules\Sales\Entities;

use Illuminate\Database\Eloquent\Model;

class SaleOrderAmountCollection extends Model
{

    const PAYMENT_COLLECTION_METHOD_CASH = 'cash';
    const PAYMENT_COLLECTION_METHOD_CARD = 'card';
    const PAYMENT_COLLECTION_METHOD_ONLINE = 'online';
    const PAYMENT_COLLECTION_METHODS = [
        self::PAYMENT_COLLECTION_METHOD_CASH,
        self::PAYMENT_COLLECTION_METHOD_CARD,
        self::PAYMENT_COLLECTION_METHOD_ONLINE
    ];

    const PAYMENT_COLLECTION_STATUS_PAID = 'paid';
    const PAYMENT_COLLECTION_STATUS_DUE = 'due';
    const PAYMENT_COLLECTION_STATUS_RETURNED = 'returned';
    const PAYMENT_COLLECTION_STATUS_REFUNDED = 'refunded';
    const PAYMENT_COLLECTION_STATUSES = [
        self::PAYMENT_COLLECTION_STATUS_PAID,
        self::PAYMENT_COLLECTION_STATUS_DUE,
        self::PAYMENT_COLLECTION_STATUS_RETURNED,
        self::PAYMENT_COLLECTION_STATUS_REFUNDED
    ];

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'sale_order_amount_collection';

    /**
     * The attributes that are mass assignable.
     *
     * @var string[]
     */
    protected $fillable = [
        'order_id',
        'method',
        'amount',
        'status',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array
     */
    protected $hidden = [];

    /**
     * Fetches the Sale Order Data of the Order Amount Collection Data.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function saleOrder() {
        return $this->belongsTo(SaleOrder::class, 'order_id', 'id');
    }

}
