<?php

namespace Modules\Vehicle\Entities;

use Illuminate\Database\Eloquent\Model;

class VehicleZoneMap extends Model
{

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'vehicle_zone_maps';

    /**
     * The attributes that are mass assignable.
     *
     * @var string[]
     */
    protected $fillable = [
        'vehicle_id',
        'zone_id',
        'is_active',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array
     */
    protected $hidden = [];

    /**
     * Fetches the Vehicle details of the Map.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function vehicleDetails() {
        return $this->belongsTo(Vehicles::class, 'vehicle_id', 'id');
    }

    /**
     * Fetches the Vehicle-Zone details of the Map.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function zoneDetails() {
        return $this->belongsTo(VehicleZone::class, 'zone_id', 'id');
    }

}
