<?php

namespace Modules\UserRole\Entities;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PermissionMap extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var string[]
     */
    protected $fillable = [
        'role_id',
        'permission_id',
        'permitted',
        'is_active',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array
     */
    protected $hidden = [];

    /**
     * Fetches the mapped User Role Data.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function mappedRole() {
        return $this->belongsTo(UserRole::class, 'role_id', 'id');
    }

    /**
     * Fetches the mapped Permission Data.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function mappedPermission() {
        return $this->belongsTo(Permission::class, 'permission_id', 'id');
    }

}
