<?php

namespace Modules\UserAddress\Entities;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;

class ProofType extends Model
{

    const PROOF_TYPE_IDENTIFICATION = 'id_proof';
    const PROOF_TYPE_ADDRESS = 'address_proof';

    const PROOF_TYPES_LIST = [
        self::PROOF_TYPE_IDENTIFICATION,
        self::PROOF_TYPE_ADDRESS
    ];

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'proof_types';

    /**
     * The attributes that are mass assignable.
     *
     * @var string[]
     */
    protected $fillable = [
        'type',
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
     * Fetches the User Map of the User Proof.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function userProofMap() {
        return $this->hasMany(UserProof::class, 'type_id', 'id');
    }

    /**
     * Get the details of the User mapped to the Proof.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany
     */
    public function mappedUser() {
        return $this->belongsToMany(
            User::class,
            (new UserProof())->getTable(),
            'type_id',
            'user_id'
        )->withPivot('path', 'is_active')->withTimestamps();
    }

}
