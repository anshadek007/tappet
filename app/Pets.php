<?php

namespace App;

use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Model;

class Pets extends Model {

    use SoftDeletes;

    protected $primaryKey = 'pet_id';
    protected $table = 'pets';

    const CREATED_AT = "pet_created_at";
    const UPDATED_AT = "pet_updated_at";
    const DELETED_AT = "pet_deleted_at";

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

    public static function boot() {
        parent::boot();

        static::deleting(function($pet) {
            $pet->co_owners()->delete();
            $pet->co_owners()->forceDelete();

            $pet->images()->delete();
            $pet->images()->forceDelete();
        });
    }

    public function addedBy() {
        return $this->hasOne('App\User', "u_id", "pet_owner_id");
    }

    public function pet_type() {
        return $this->hasOne('App\PetTypes', "pt_id", "pet_type_id");
    }
    
    public function collar() {
        return $this->hasOne('App\PetCollars', "pet_collar_pet_id", "pet_id");
    }

    public function images() {
        return $this->hasMany('App\PetImages', "pi_pet_id", "pet_id");
    }

    public function co_owners() {
        return $this->hasMany('App\PetCoOwners', "pet_co_owner_pet_id", "pet_id");
    }

    public function getPetImageAttribute() {
        $link = '';
        if (!empty($this->attributes['pet_image'])) {
            $link = getPhotoURL(config("constants.UPLOAD_PETS_FOLDER"), $this->attributes['pet_id'], $this->attributes['pet_image']);
        } else {
            $link = url('/public/assets/images/' . config("constants.DEFAULT_PLACEHOLDER_IMAGE"));
        }
        return $link;
    }

    public function getPtImageAttribute() {
        $link = '';
        if (!empty($this->attributes['pt_image'])) {
            $link = getPhotoURL(config("constants.UPLOAD_PET_TYPES_FOLDER"), $this->attributes['pt_id'], $this->attributes['pt_image']);
        } else {
            $link = url('/public/assets/images/' . config("constants.DEFAULT_PLACEHOLDER_IMAGE"));
        }
        return $link;
    }

}
