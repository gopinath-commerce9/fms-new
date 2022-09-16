<?php

namespace Modules\Sales\Entities;

use Illuminate\Database\Eloquent\Model;

class SalesRegion extends Model
{

    const KERABIYA_ACCESS_ENABLED = 1;
    const KERABIYA_ACCESS_DISABLED = 0;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'sales_regions';

    /**
     * The attributes that are mass assignable.
     *
     * @var string[]
     */
    protected $fillable = [
        'env',
        'channel',
        'entity_id',
        'region_id',
        'country_id',
        'kerabiya_access',
        'name',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array
     */
    protected $hidden = [];

    /**
     * Checks whether the Region has Kerabiya Logistics Access enabled.
     * @return bool
     */
    public function isKerabiyaEnabled() {
        return ($this->kerabiya_access === self::KERABIYA_ACCESS_ENABLED)
            ? true
            : false;
    }

}
