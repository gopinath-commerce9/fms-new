<?php

namespace App\Models;

use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Modules\Sales\Entities\SaleOrderProcessHistory;
use Modules\UserAddress\Entities\ProofType;
use Modules\UserAddress\Entities\UserAddress;
use Modules\UserAddress\Entities\UserProof;
use Modules\UserRole\Entities\UserRole;
use Modules\UserRole\Entities\UserRoleMap;
use Modules\Vehicle\Entities\UserVehicleMap;
use Modules\Vehicle\Entities\Vehicles;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var string[]
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'contact_number',
        'profile_picture',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
    ];

    /**
     * Fetches the Role Map Data of the User.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasOne
     */
    public function roleMap() {
        return $this->hasOne(UserRoleMap::class, 'user_id', 'id');
    }

    /**
     * Fetches the mapped User Role data.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany
     */
    public function  mappedRole() {
        return $this->belongsToMany(
            UserRole::class,
            (new UserRoleMap())->getTable(),
            'user_id',
            'role_id'
        )->withPivot('is_feeder_driver', 'is_active')->withTimestamps();
    }

    /**
     * Checks whether the User is the Default User of the Website.
     * @return bool
     */
    public function isDefaultUser() {
        $defaultAdmin = config('userroles.default.admin_user');
        return (is_array($defaultAdmin) && array_key_exists('email', $defaultAdmin) && ($this->email === $defaultAdmin['email']))
            ? true
            : false;
    }

    /**
     * Get the Addresses of the User.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function userAddresses() {
        return $this->hasMany(UserAddress::class, 'user_id', 'id');
    }

    /**
     * Fetches the User Proof Map of the User.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function userProofMap() {
        return $this->hasMany(UserProof::class, 'user_id', 'id');
    }

    /**
     * The Proofs submitted by the User.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany
     */
    public function userProofs() {
        return $this->belongsToMany(
            ProofType::class,
            (new UserProof())->getTable(),
            'user_id',
            'type_id'
        )->withPivot('path', 'is_active')->withTimestamps();
    }

    /**
     * The vehicles owned by the User.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function ownedVehicles() {
        return $this->hasMany(Vehicles::class, 'owner_id', 'id');
    }

    /**
     * The Assigned Vehicle Map of the User.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function assignedVehiclesMap() {
        return $this->hasMany(UserVehicleMap::class, 'user_id', 'id');
    }

    /**
     * The Vehicles assigned to the User.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany
     */
    public function assignedVehicles() {
        return $this->belongsToMany(
            Vehicles::class,
            (new UserVehicleMap())->getTable(),
            'user_id',
            'vehicle_id'
        )->withPivot('is_active')->withTimestamps();
    }

    /**
     * Fetches the History of the Sale Orders handled by the User.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function saleOrderProcessHistory() {
        return $this->hasMany(SaleOrderProcessHistory::class, 'done_by', 'id');
    }

}
