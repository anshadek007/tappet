<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Notification extends Model {

    use SoftDeletes;

    protected $primaryKey = 'n_id';

    const CREATED_AT = "n_created_at";
    const UPDATED_AT = "n_updated_at";
    const DELETED_AT = "n_deleted_at";

    protected $fillable = [];
    protected $guarded = [];

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
