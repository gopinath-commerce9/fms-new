<?php

namespace Modules\Sales\Entities;

use Illuminate\Database\Eloquent\Model;

class ProductCategory extends Model
{

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'product_categories';

    /**
     * The attributes that are mass assignable.
     *
     * @var string[]
     */
    protected $fillable = [
        'env',
        'channel',
        'product_id',
        'product_sku',
        'product_name',
        'category_id',
        'category_name',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array
     */
    protected $hidden = [];

}
