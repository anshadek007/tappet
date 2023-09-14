<?php

namespace App;

use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Model;

class PetTypes extends Model {

    use SoftDeletes;

    protected $primaryKey = 'pt_id';
    protected $table = 'pet_types';

    const CREATED_AT = "pt_created_at";
    const UPDATED_AT = "pt_updated_at";
    const DELETED_AT = "pt_deleted_at";

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
