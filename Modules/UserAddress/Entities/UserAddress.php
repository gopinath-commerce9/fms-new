<?php

namespace Modules\UserAddress\Entities;


use App\Models\User;
use Illuminate\Database\Eloquent\Model;

class UserAddress extends Model
{

    const USER_ADDRESS_PRIMARY_YES = 1;
    const USER_ADDRESS_PRIMARY_NO = 0;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'user_addresses';

    /**
     * The attributes that are mass assignable.
     *
     * @var string[]
     */
    protected $fillable = [
        'user_id',
        'label',
        'first_name',
        'last_name',
        'address_street_1',
        'address_street_2',
        'city',
        'region',
        'state',
        'zip_code',
        'country',
        'phone_number',
        'mobile_number',
        'notes',
        'is_primary',
        'is_active',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array
     */
    protected $hidden = [];

    /**
     * Fetches the data of the User which the Address belongs to.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function userDetails() {
        return $this->belongsTo(User::class, 'user_id', 'id');
    }

    /**
     * Check whether the User Address is Primary.
     *
     * @return bool
     */
    public function isPrimary() {
        return ($this->is_primary == self::USER_ADDRESS_PRIMARY_YES)
            ? true
            : false;
    }

}
