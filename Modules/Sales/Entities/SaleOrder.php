<?php

namespace Modules\Sales\Entities;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;

class SaleOrder extends Model
{

    const AVAILABLE_ORDER_STATUSES = [
        'pending',
        'ngenius_pending',
        'processing',
        'ngenius_processing',
        'ngenius_complete',
        'being_prepared',
        'holded',
        'order_updated',
        'ready_to_dispatch',
        'out_for_delivery',
        'delivered',
        'canceled',
    ];

    const SALE_ORDER_STATUS_PENDING = 'pending';
    const SALE_ORDER_STATUS_NGENIUS_PENDING = 'ngenius_pending';
    const SALE_ORDER_STATUS_PROCESSING = 'processing';
    const SALE_ORDER_STATUS_NGENIUS_PROCESSING = 'ngenius_processing';
    const SALE_ORDER_STATUS_NGENIUS_COMPLETE = 'ngenius_complete';
    const SALE_ORDER_STATUS_BEING_PREPARED = 'being_prepared';
    const SALE_ORDER_STATUS_ON_HOLD = 'holded';
    const SALE_ORDER_STATUS_ORDER_UPDATED = 'order_updated';
    const SALE_ORDER_STATUS_READY_TO_DISPATCH = 'ready_to_dispatch';
    const SALE_ORDER_STATUS_OUT_FOR_DELIVERY = 'out_for_delivery';
    const SALE_ORDER_STATUS_DELIVERED = 'delivered';
    const SALE_ORDER_STATUS_CANCELED = 'canceled';

    const COLLECTION_VERIFIED_YES = 1;
    const COLLECTION_VERIFIED_NO = 0;

    const KERABIYA_DELIVERY_YES = 1;
    const KERABIYA_DELIVERY_NO = 0;

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
        'delivery_notes',
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
        'eco_friendly_packing_fee',
        'store_credits_used',
        'store_credits_invoiced',
        'canceled_total',
        'invoiced_total',
        'order_due',
        'order_state',
        'order_status',
        'order_status_label',
        'invoiced_at',
        'invoice_id',
        'invoice_number',
        'to_be_synced',
        'is_synced',
        'is_kerabiya_delivery',
        'kerabiya_set_at',
        'kerabiya_set_by',
        'kerabiya_awb_number',
        'kerabiya_awb_pdf',
        'is_active',
        'is_amount_verified',
        'amount_verified_at',
        'amount_verified_by',
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

    /**
     * Fetches the Data about the Payment collected during the Sale Order Customer Delivery.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function paymentCollections() {
        return $this->hasMany(SaleOrderAmountCollection::class, 'order_id', 'id');
    }

    /**
     * Fetches the Data about the Amount paid and collected during the Sale Order Customer Delivery.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function paidAmountCollections() {
        return $this->hasMany(SaleOrderAmountCollection::class, 'order_id', 'id')
            ->where('status',  SaleOrderAmountCollection::PAYMENT_COLLECTION_STATUS_PAID);
    }

    /**
     * Fetches the Data about the Amount collected through Cash during the Sale Order Customer Delivery.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function cashAmountCollections() {
        return $this->hasMany(SaleOrderAmountCollection::class, 'order_id', 'id')
            ->where('method',  SaleOrderAmountCollection::PAYMENT_COLLECTION_METHOD_CASH);
    }

    /**
     * Fetches the Data about the Amount collected through Card during the Sale Order Customer Delivery.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function cardAmountCollections() {
        return $this->hasMany(SaleOrderAmountCollection::class, 'order_id', 'id')
            ->where('method',  SaleOrderAmountCollection::PAYMENT_COLLECTION_METHOD_CARD);
    }

    /**
     * Fetches the Data about the Amount collected through Online during the Sale Order Customer Delivery.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function onlineAmountCollections() {
        return $this->hasMany(SaleOrderAmountCollection::class, 'order_id', 'id')
            ->where('method',  SaleOrderAmountCollection::PAYMENT_COLLECTION_METHOD_ONLINE);
    }

    /**
     * Fetches the User data who executed the Driver Amount Collection Verification.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function collectionVerifier() {
        return $this->belongsTo(User::class, 'amount_verified_by', 'id');
    }

    /**
     * Checks whether the Order is delivered through Kerabiya Logistics.
     * @return bool
     */
    public function isKerabiyaDelivery() {
        return ($this->is_kerabiya_delivery === self::KERABIYA_DELIVERY_YES)
            ? true
            : false;
    }

    /**
     * Checks whether the Order amount collection is verified.
     * @return bool
     */
    public function isCollectionVerified() {
        return ($this->is_amount_verified === self::COLLECTION_VERIFIED_YES)
            ? true
            : false;
    }

}
