<?php

namespace Modules\API\Entities;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Modules\UserRole\Entities\UserRole;

class MobileAppUser extends Model
{
    use HasFactory;

    const USER_LOGGED_IN_YES = 1;
    const USER_LOGGED_IN_NO = 0;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'mobile_app_users';

    /**
     * The attributes that are mass assignable.
     *
     * @var string[]
     */
    protected $fillable = [
        'user_id',
        'role_id',
        'access_token',
        'device_id',
        'onesignal_player_id',
        'firebase_token_id',
        'last_seen_lat',
        'last_seen_lng',
        'last_seen_at',
        'notes',
        'logged_in',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array
     */
    protected $hidden = [];

    /**
     * Fetches the Mapped User Data.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function mappedUser() {
        return $this->belongsTo(User::class, 'user_id', 'id');
    }

    /**
     * Fetches the Mapped User Role Data.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function mappedRole() {
        return $this->belongsTo(UserRole::class, 'role_id', 'id');
    }

    /**
     * Checks whether the Mobile App User is Logged In.
     * @return bool
     */
    public function isUserLoggedIn() {
        return ($this->logged_in == self::USER_LOGGED_IN_YES)
            ? true
            : false;
    }

}
