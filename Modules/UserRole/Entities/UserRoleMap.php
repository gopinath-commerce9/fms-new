<?php

namespace Modules\UserRole\Entities;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class UserRoleMap extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var string[]
     */
    protected $fillable = [
        'user_id',
        'role_id',
        'is_active',
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

}
