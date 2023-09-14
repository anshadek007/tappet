<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class PostLikes extends Model {

    protected $primaryKey = 'post_like_id';
    protected $table = 'post_likes';
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

    public function user() {
        return $this->hasOne('App\User', "u_id", "post_like_user_id");
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
