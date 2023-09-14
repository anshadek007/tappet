<?php

namespace App;

use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Model;

class PetCoOwners extends Model {

    use SoftDeletes;

    protected $primaryKey = 'pet_co_owner_id';
    protected $table = 'pet_co_owners';

    const CREATED_AT = "pet_co_owner_created_at";
    const UPDATED_AT = "pet_co_owner_updated_at";
    const DELETED_AT = "pet_co_owner_deleted_at";

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [];
    protected $guarded = [];

    /**
     * The attributes that should be hidden for arrays.
     *
     * @var array
     */
    protected $hidden = [];

    public function member() {
        return $this->hasOne('App\User', "u_id", "pet_co_owner_owner_id");
    }

    public function getUImageAttribute() {
        $link = '';
        if (!empty($this->attributes['u_image'])) {
            $link = getPhotoURL(config('constants.UPLOAD_USERS_FOLDER'), $this->attributes['u_id'], $this->attributes['u_image']);
        } else {
            $link = url('/public/assets/images/' . config("constants.DEFAULT_PLACEHOLDER_IMAGE"));
        }
        return $link;
    }

}
