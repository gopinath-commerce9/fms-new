<?php

namespace Modules\Vehicle\Entities;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;

class Vehicles extends Model
{

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'vehicles';

    /**
     * The attributes that are mass assignable.
     *
     * @var string[]
     */
    protected $fillable = [
        'type',
        'reg_number',
        'chassis_number',
        'maker',
        'model',
        'class',
        'fuel_type',
        'color',
        'vehicle_picture',
        'insurance_number',
        'insurance_due',
        'inc_papers',
        'pollution_certificate',
        'pollution_due',
        'pollution_papers',
        'fitness_certificate',
        'fitness_due',
        'fitness_papers',
        'registration_certificate',
        'rc_papers',
        'owner_name',
        'owner_id',
        'last_serviced_at',
        'last_serviced_station',
        'last_distance',
        'distance_unit',
        'last_distance_at',
        'notes',
        'is_active',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array
     */
    protected $hidden = [];

    /**
     * The Assigned User Map of the Vehicle.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function assignedUserMap() {
        return $this->hasMany(UserVehicleMap::class, 'vehicle_id', 'id');
    }

    /**
     * Fetches the Users mapped to the Vehicle.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany
     */
    public function assignedUsers() {
        return $this->belongsToMany(
            User::class,
            (new UserVehicleMap())->getTable(),
            'vehicle_id',
            'user_id'
        )->withPivot('is_active')->withTimestamps();
    }

    /**
     * Fetches the Vehicle-Zone Map of the Vehicle.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function zoneMap() {
        return $this->hasMany(VehicleZoneMap::class, 'vehicle_id', 'id');
    }

    /**
     * Fetches the Vehicle-Zones mapped to the Vehicle.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany
     */
    public function mappedZones() {
        return $this->belongsToMany(
            VehicleZone::class,
            (new VehicleZoneMap())->getTable(),
            'vehicle_id',
            'zone_id'
        )->withPivot('is_active')->withTimestamps();
    }

}
