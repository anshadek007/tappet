<?php

namespace App;

use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Model;

class PostComments extends Model {

    use SoftDeletes;

    protected $primaryKey = 'post_comment_id';
    protected $table = 'post_comments';

    const CREATED_AT = "post_comment_created_at";
    const UPDATED_AT = "post_comment_updated_at";
    const DELETED_AT = "post_comment_deleted_at";

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
        return $this->hasOne('App\User', "u_id", "post_comment_user_id");
    }

    public function corporateUser() {
        return $this->hasOne('App\BusinessUser', "post_comment_user_id");
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
