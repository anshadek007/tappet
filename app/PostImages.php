<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class PostImages extends Model {

    protected $primaryKey = 'post_image_id';
    protected $table = 'post_images';
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

    public function getPostImageImageAttribute() {
        $link = '';
        if (!empty($this->attributes['post_image_image'])) {
            $link = getPhotoURL(config("constants.UPLOAD_POSTS_FOLDER"), $this->attributes['post_image_post_id'], $this->attributes['post_image_image']);
        } else {
            $link = url('/public/assets/images/' . config("constants.DEFAULT_PLACEHOLDER_IMAGE"));
        }
        return $link;
    }

}
