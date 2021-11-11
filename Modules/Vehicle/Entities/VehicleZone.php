<?php

namespace Modules\Vehicle\Entities;

use Illuminate\Database\Eloquent\Model;

class VehicleZone extends Model
{

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'vehicle_zones';

    /**
     * The attributes that are mass assignable.
     *
     * @var string[]
     */
    protected $fillable = [
        'code',
        'display_name',
        'description',
        'is_active',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array
     */
    protected $hidden = [];

    /**
     * Fetches the Vehicle Map of the Vehicle-Zone.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function vehicleMap() {
        return $this->hasMany(VehicleZoneMap::class, 'zone_id', 'id');
    }

    /**
     * Fetches the Vehicles mapped to the Vehicle-Zone.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany
     */
    public function mappedVehicles() {
        return $this->belongsToMany(
            Vehicles::class,
            (new VehicleZoneMap())->getTable(),
            'zone_id',
            'vehicle_id'
        )->withPivot('is_active')->withTimestamps();
    }

}
