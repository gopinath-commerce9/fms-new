<?php

namespace Modules\Sales\Entities;

use Illuminate\Database\Eloquent\Model;

class SaleCustomer extends Model
{

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'sale_customers';

    /**
     * The attributes that are mass assignable.
     *
     * @var string[]
     */
    protected $fillable = [
        'env',
        'channel',
        'contact_number',
        'email_id',
        'customer_group_id',
        'sale_customer_id',
        'first_name',
        'last_name',
        'gender',
        'is_active',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array
     */
    protected $hidden = [];

    /**
     * Fetches all the Sale Orders placed by the Customer.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function saleOrders() {
        return $this->hasMany(SaleOrder::class, 'customer_id', 'id');
    }

}
