<?php

namespace Modules\Sales\Entities;

use Illuminate\Database\Eloquent\Model;

class SaleOrderAddress extends Model
{

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'sale_order_addresses';

    /**
     * The attributes that are mass assignable.
     *
     * @var string[]
     */
    protected $fillable = [
        'order_id',
        'address_id',
        'sale_order_id',
        'type',
        'first_name',
        'last_name',
        'email_id',
        'address_1',
        'address_2',
        'address_3',
        'city',
        'region_id',
        'region_code',
        'region',
        'country_id',
        'post_code',
        'latitude',
        'longitude',
        'contact_number',
        'is_active',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array
     */
    protected $hidden = [];

    /**
     * Fetches the Sale Order Data of the Order Item.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function saleOrder() {
        return $this->belongsTo(SaleOrder::class, 'order_id', 'id');
    }

}
