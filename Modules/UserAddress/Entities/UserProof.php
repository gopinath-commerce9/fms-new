<?php

namespace Modules\UserAddress\Entities;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;

class UserProof extends Model
{

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'user_proofs';

    /**
     * The attributes that are mass assignable.
     *
     * @var string[]
     */
    protected $fillable = [
        'user_id',
        'type_id',
        'path',
        'is_active',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array
     */
    protected $hidden = [];

    /**
     * Fetches the details of the User mapped.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function userDetails() {
        return $this->belongsTo(User::class, 'user_id', 'id');
    }

    /**
     * Fetches the details of the Proof mapped.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function proofDetails() {
        return $this->belongsTo(ProofType::class, 'type_id', 'id');
    }

}
