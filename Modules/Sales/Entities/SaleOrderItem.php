<?php

namespace Modules\Sales\Entities;

use Illuminate\Database\Eloquent\Model;

class SaleOrderItem extends Model
{

    const STORE_AVAILABLE_YES = 1;
    const STORE_AVAILABLE_NO = 0;
    const STORE_AVAILABLE_NOT_CHECKED = null;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'sale_order_items';

    /**
     * The attributes that are mass assignable.
     *
     * @var string[]
     */
    protected $fillable = [
        'order_id',
        'item_id',
        'sale_order_id',
        'item_created_at',
        'item_updated_at',
        'product_id',
        'product_type',
        'item_sku',
        'item_barcode',
        'item_name',
        'item_info',
        'item_image',
        'scan_barcode',
        'scan_count',
        'actual_qty',
        'qty_ordered',
        'qty_delivered',
        'qty_shipped',
        'qty_invoiced',
        'qty_canceled',
        'qty_returned',
        'qty_refunded',
        'selling_unit',
        'selling_unit_label',
        'billing_period',
        'delivery_day',
        'scale_number',
        'country_label',
        'item_weight',
        'price',
        'row_total',
        'tax_amount',
        'tax_percent',
        'discount_amount',
        'discount_percent',
        'row_grand_total',
        'row_total_delivered',
        'vendor_id',
        'vendor_availability',
        'store_availability',
        'availability_checked_at',
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

    /**
     * Fetches the Product Category Details of the Sale Order Item.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasOne
     */
    public function productCategory() {
        return $this->hasOne(ProductCategory::class, 'product_id', 'product_id');
    }

    /**
     * Checks whether the given Sale Order Item is Available at the Store.
     *
     * @return bool
     */
    public function isStoreAvailable() {
        return ($this->store_availability === self::STORE_AVAILABLE_YES)
            ? true
            : false;
    }

    /**
     * Checks whether the given Sale Order Item is Not Available at the Store.
     *
     * @return bool
     */
    public function isNotStoreAvailable() {
        return ($this->store_availability === self::STORE_AVAILABLE_NO)
            ? true
            : false;
    }

    /**
     * Checks whether the given Sale Order Item is Not Checked the Availability at the Store.
     *
     * @return bool
     */
    public function isNotCheckedStoreAvailable() {
        return ($this->store_availability === self::STORE_AVAILABLE_NOT_CHECKED)
            ? true
            : false;
    }

}
