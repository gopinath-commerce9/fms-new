<?php

namespace Modules\Sales\Entities;

use Illuminate\Database\Eloquent\Model;

class SaleOrder extends Model
{

    const AVAILABLE_ORDER_STATUSES = [
        'pending',
        'processing',
        'being_prepared',
        'holded',
        'order_updated',
        'ready_to_dispatch',
        'out_for_delivery',
        'delivered',
        'canceled',
    ];

    const SALE_ORDER_STATUS_PENDING = 'pending';
    const SALE_ORDER_STATUS_PROCESSING = 'processing';
    const SALE_ORDER_STATUS_BEING_PREPARED = 'being_prepared';
    const SALE_ORDER_STATUS_ON_HOLD = 'holded';
    const SALE_ORDER_STATUS_ORDER_UPDATED = 'order_updated';
    const SALE_ORDER_STATUS_READY_TO_DISPATCH = 'ready_to_dispatch';
    const SALE_ORDER_STATUS_OUT_FOR_DELIVERY = 'out_for_delivery';
    const SALE_ORDER_STATUS_DELIVERED = 'delivered';
    const SALE_ORDER_STATUS_CANCELED = 'canceled';

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'sale_orders';

    /**
     * The attributes that are mass assignable.
     *
     * @var string[]
     */
    protected $fillable = [
        'env',
        'channel',
        'order_id',
        'increment_id',
        'order_created_at',
        'order_updated_at',
        'customer_id',
        'is_guest',
        'customer_firstname',
        'customer_lastname',
        'region_id',
        'region_code',
        'region',
        'city',
        'zone_id',
        'store',
        'delivery_date',
        'delivery_time_slot',
        'total_item_count',
        'total_qty_ordered',
        'order_weight',
        'box_count',
        'not_require_pack',
        'order_currency',
        'order_subtotal',
        'order_tax',
        'discount_amount',
        'shipping_total',
        'shipping_method',
        'order_total',
        'order_due',
        'order_state',
        'order_status',
        'order_status_label',
        'to_be_synced',
        'is_synced',
        'is_active',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array
     */
    protected $hidden = [];

    /**
     * Fetches the Customer data of the Sale Order.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function saleCustomer() {
        return $this->belongsTo(SaleCustomer::class, 'customer_id', 'id');
    }

    /**
     * Fetches the Order Items Data of the Sale Order.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function  orderItems() {
        return $this->hasMany(SaleOrderItem::class, 'order_id', 'id');
    }

    /**
     * Fetches the Billing Address of the Sale Order.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasOne
     */
    public function billingAddress() {
        return $this->hasOne(SaleOrderAddress::class, 'order_id', 'id')
            ->where('type', '=', 'billing');
    }

    /**
     * Fetches the Shipping Address of the Sale Order.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasOne
     */
    public function shippingAddress() {
        return $this->hasOne(SaleOrderAddress::class, 'order_id', 'id')
            ->where('type', '=', 'shipping');
    }

    /**
     * Fetches the Payment Data of the Sale Order.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function  paymentData() {
        return $this->hasMany(SaleOrderPayment::class, 'order_id', 'id');
    }

    /**
     * Fetches the Status History Data of the Sale Order.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function  statusHistory() {
        return $this->hasMany(SaleOrderStatusHistory::class, 'order_id', 'id');
    }

    /**
     * Fetches the Process History Data of the Sale Order.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function  processHistory() {
        return $this->hasMany(SaleOrderProcessHistory::class, 'order_id', 'id');
    }

    /**
     * Fetches the Pickup Process Data of the Sale Order.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function pickupData() {
        return $this->hasMany(SaleOrderProcessHistory::class, 'order_id', 'id')
            ->whereIn('action',  SaleOrderProcessHistory::SALE_ORDER_PROCESS_PICKUP_ACTIONS);
    }

    /**
     * Fetches the Delivery Process Data of the Sale Order.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function deliveryData() {
        return $this->hasMany(SaleOrderProcessHistory::class, 'order_id', 'id')
            ->whereIn('action',  SaleOrderProcessHistory::SALE_ORDER_PROCESS_DELIVERY_ACTIONS);
    }

    /**
     * Fetches the Data about who assigned the processing of the Sale Order.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function  assignerData() {
        return $this->hasMany(SaleOrderProcessHistory::class, 'order_id', 'id')
            ->whereIn('action',  SaleOrderProcessHistory::SALE_ORDER_PROCESS_ASSIGNED_ACTIONS);
    }

    /**
     * Fetches the Data about Process Completion of the Sale Order.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function completedData() {
        return $this->hasMany(SaleOrderProcessHistory::class, 'order_id', 'id')
            ->whereIn('action',  SaleOrderProcessHistory::SALE_ORDER_PROCESS_COMPLETED_ACTIONS);
    }

    /**
     * Fetches the Data about who is currently assigned to Pickup the Sale Order.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function currentPicker() {
        return $this->hasMany(SaleOrderProcessHistory::class, 'order_id', 'id')
            ->where('action',  SaleOrderProcessHistory::SALE_ORDER_PROCESS_ACTION_PICKUP)
            ->orderBy('done_at', 'desc')
            ->limit(1);
    }

    /**
     * Fetches the Data about who currently assigned the Picker for the Sale Order.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function currentPickerAssigner() {
        return $this->hasMany(SaleOrderProcessHistory::class, 'order_id', 'id')
            ->where('action',  SaleOrderProcessHistory::SALE_ORDER_PROCESS_ACTION_PICKUP_ASSIGN)
            ->orderBy('done_at', 'desc')
            ->limit(1);
    }

    /**
     * Fetches the Data about who is currently assigned to Deliver the Sale Order.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function currentDriver() {
        return $this->hasMany(SaleOrderProcessHistory::class, 'order_id', 'id')
            ->where('action',  SaleOrderProcessHistory::SALE_ORDER_PROCESS_ACTION_DELIVERY)
            ->orderBy('done_at', 'desc')
            ->limit(1);
    }

    /**
     * Fetches the Data about who currently assigned the Driver for the Sale Order.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function currentDriverAssigner() {
        return $this->hasMany(SaleOrderProcessHistory::class, 'order_id', 'id')
            ->where('action',  SaleOrderProcessHistory::SALE_ORDER_PROCESS_ACTION_DELIVERY_ASSIGN)
            ->orderBy('done_at', 'desc')
            ->limit(1);
    }

    /**
     * Fetches the Data about who picked the Sale Order.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasOne
     */
    public function pickedData() {
        return $this->hasOne(SaleOrderProcessHistory::class, 'order_id', 'id')
            ->where('action',  SaleOrderProcessHistory::SALE_ORDER_PROCESS_ACTION_PICKED);
    }

    /**
     * Fetches the Data about who delivered the Sale Order.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasOne
     */
    public function deliveredData() {
        return $this->hasOne(SaleOrderProcessHistory::class, 'order_id', 'id')
            ->where('action',  SaleOrderProcessHistory::SALE_ORDER_PROCESS_ACTION_DELIVERED);
    }

    /**
     * Fetches the Data about who canceled the Sale Order.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasOne
     */
    public function canceledData() {
        return $this->hasOne(SaleOrderProcessHistory::class, 'order_id', 'id')
            ->where('action',  SaleOrderProcessHistory::SALE_ORDER_PROCESS_ACTION_CANCELED);
    }

}
