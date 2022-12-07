<?php

namespace Modules\UserRole\Entities;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\User;

class UserRole extends Model
{
    use HasFactory;

    const USER_ROLE_ADMIN = 'admin';
    const USER_ROLE_SUPERVISOR = 'supervisor';
    const USER_ROLE_PICKER = 'picker';
    const USER_ROLE_DRIVER = 'driver';
    const USER_ROLE_UNASSIGNED = null;

    const ROLE_USER_ACTIVE_YES = 1;
    const ROLE_USER_ACTIVE_NO = 0;

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
     * Fetches the User Map Data of the Role.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function userMap() {
        return $this->hasMany(UserRoleMap::class, 'role_id', 'id');
    }

    /**
     * Fetches the Permissions Map Data of the Role.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function permissionMap() {
        return $this->hasMany(PermissionMap::class, 'role_id', 'id');
    }

    /**
     * Fetches the mapped Users Data
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany
     */
    public function mappedUsers() {
        return $this->belongsToMany(
            User::class,
            (new UserRoleMap())->getTable(),
            'role_id',
            'user_id'
        )->withPivot('is_feeder_driver', 'is_active')->withTimestamps();
    }

    /**
     * Fetches the mapped Permissions Data
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany
     */
    public function mappedPermissions() {
        return $this->belongsToMany(
            Permission::class,
            (new PermissionMap())->getTable(),
            'role_id',
            'permission_id'
        )->withPivot('permitted', 'is_active')->withTimestamps();
    }

    /**
     * Checks whether the Role is Admin.
     * @return bool
     */
    public function isAdmin() {
        return ($this->code === self::USER_ROLE_ADMIN)
            ? true
            : false;
    }

    /**
     * Checks whether the Role is Supervisor.
     * @return bool
     */
    public function isSupervisor() {
        return ($this->code === self::USER_ROLE_SUPERVISOR)
            ? true
            : false;
    }

    /**
     * Checks whether the Role is Picker.
     * @return bool
     */
    public function isPicker() {
        return ($this->code === self::USER_ROLE_PICKER)
            ? true
            : false;
    }

    /**
     * Checks whether the Role is Driver.
     * @return bool
     */
    public function isDriver() {
        return ($this->code === self::USER_ROLE_DRIVER)
            ? true
            : false;
    }

    /**
     * Checks whether the Role is Un-Assigned.
     * @return bool
     */
    public function isUnassigned() {
        return ($this->code === self::USER_ROLE_UNASSIGNED)
            ? true
            : false;
    }

    /**
     * Fetches all the Users with the UserRole 'Admin'
     *
     * @return \Modules\UserRole\Entities\UserRole|null
     */
    public function allAdmins() {
        $adminObj = self::where('code', self::USER_ROLE_ADMIN)->get();
        if (is_null($adminObj)) {
            return null;
        } else {
            $adminRole = $adminObj->first();
            $adminRole->mappedUsers;
            return $adminRole;
        }
    }

    /**
     * Fetches all the Users with the UserRole 'Supervisor'
     *
     * @return \Modules\UserRole\Entities\UserRole|null
     */
    public function allSupervisors() {
        $supervisorObj = self::where('code', self::USER_ROLE_SUPERVISOR)->get();
        if (is_null($supervisorObj)) {
            return null;
        } else {
            $supervisorRole = $supervisorObj->first();
            $supervisorRole->mappedUsers;
            return $supervisorRole;
        }
    }

    /**
     * Fetches all the Users with the UserRole 'Picker'
     *
     * @return \Modules\UserRole\Entities\UserRole|null
     */
    public function allPickers() {
        $pickerObj = self::where('code', self::USER_ROLE_PICKER)->get();
        if (is_null($pickerObj)) {
            return null;
        } else {
            $pickerRole = $pickerObj->first();
            $pickerRole->mappedUsers;
            return $pickerRole;
        }
    }

    /**
     * Fetches all the Users with the UserRole 'Driver'
     *
     * @return \Modules\UserRole\Entities\UserRole|null
     */
    public function allDrivers() {
        $driverObj = self::where('code', self::USER_ROLE_DRIVER)->get();
        if (is_null($driverObj)) {
            return null;
        } else {
            $driverRole = $driverObj->first();
            $driverRole->mappedUsers;
            return $driverRole;
        }
    }

}
