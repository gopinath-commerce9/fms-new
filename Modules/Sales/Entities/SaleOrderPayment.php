<?php

namespace Modules\Sales\Entities;

use Illuminate\Database\Eloquent\Model;

class SaleOrderPayment extends Model
{

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'sale_order_payments';

    /**
     * The attributes that are mass assignable.
     *
     * @var string[]
     */
    protected $fillable = [
        'order_id',
        'payment_id',
        'sale_order_id',
        'method',
        'amount_payable',
        'amount_paid',
        'cc_last4',
        'cc_start_month',
        'cc_start_year',
        'cc_exp_year',
        'shipping_amount',
        'shipping_captured',
        'extra_info',
        'is_active',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array
     */
    protected $hidden = [];

    /**
     * Fetches the Sale Order Data of the Payment.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function saleOrder() {
        return $this->belongsTo(SaleOrder::class, 'order_id', 'id');
    }

}
