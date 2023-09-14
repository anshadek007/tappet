<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class PetImages extends Model {

    protected $primaryKey = 'pi_id';
    protected $table = 'pet_images';
    public $timestamps = false;

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

    public function getPiImageAttribute() {
        $link = '';
        if (!empty($this->attributes['pi_image'])) {
            $link = getPhotoURL(config("constants.UPLOAD_PETS_FOLDER"), $this->attributes['pi_pet_id'], $this->attributes['pi_image']);
        } else {
            $link = url('/public/assets/images/' . config("constants.DEFAULT_PLACEHOLDER_IMAGE"));
        }
        return $link;
    }

}
