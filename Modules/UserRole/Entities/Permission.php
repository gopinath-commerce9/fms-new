<?php

namespace Modules\UserRole\Entities;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Permission extends Model
{
    use HasFactory;

    const PERMITTED_YES = 1;
    const PERMITTED_NO = 0;

    const DEFAULT_PERMISSIONS = [
        'users.view',
        'users.create',
        'users.update',
        'users.delete',
        'user-roles.view',
        'user-roles.create',
        'user-roles.update',
        'user-roles.assign',
        'user-roles.delete',
        'user-role-permissions.view',
        'user-role-permissions.create',
        'user-role-permissions.update',
        'user-role-permissions.grant',
        'user-role-permissions.delete',
    ];

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
     * Fetches the User Role Map Data of the Permission.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function roleMap() {
        return $this->hasMany(PermissionMap::class, 'permission_id', 'id');
    }

    /**
     * Fetches the mapped User Role Data
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany
     */
    public function mappedRoles() {
        return $this->belongsToMany(
            UserRole::class,
            (new PermissionMap())->getTable(),
            'permission_id',
            'role_id'
        )->withPivot('permitted', 'is_active')->withTimestamps();
    }

    /**
     * Checks whether the Permission is Default.
     * @return bool
     */
    public function isDefaultPermission() {
        return (in_array($this->code, self::DEFAULT_PERMISSIONS))
            ? true
            : false;
    }

}
