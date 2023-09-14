<?php

namespace App;

use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class Posts extends Model {

    use SoftDeletes;

    protected $primaryKey = 'post_id';
    protected $table = 'posts';

    const CREATED_AT = "post_created_at";
    const UPDATED_AT = "post_updated_at";
    const DELETED_AT = "post_deleted_at";

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

    // this is a recommended way to declare event handlers
    public static function boot() {
        parent::boot();

        static::deleting(function($event) {
            $event->post_comments()->delete();
            $event->post_comments()->forceDelete();

            $event->post_likes()->delete();
            $event->post_likes()->forceDelete();

            $event->post_images()->delete();
            $event->post_images()->forceDelete();
        });
    }

    public function addedBy() {
        return $this->hasOne('App\User', "u_id", "post_owner_id");
    }
    
    public function event() {
        return $this->hasOne('App\Events', "event_id", "post_event_id");
    }
    
    public function group() {
        return $this->hasOne('App\Groups', "group_id", "post_group_id");
    }

    public function post_comments() {
        return $this->hasMany('App\PostComments', "post_comment_post_id", "post_id");
    }

    public function post_images() {
        return $this->hasMany('App\PostImages', "post_image_post_id", "post_id");
    }

    public function post_likes() {
        return $this->hasMany('App\PostLikes', "post_like_post_id", "post_id");
    }
    
    public function post_is_liked() {
        return $this->hasOne("App\PostLikes", "post_like_post_id", "post_id")->where('post_like_user_id', Auth::user()->u_id);
    }

    public function getPostImageAttribute() {
        $link = '';
        if ($this->attributes['post_type'] !='Audio' && $this->attributes['post_type'] !='Video' && !empty($this->attributes['post_image'])) {
            $link = getPhotoURL(config("constants.UPLOAD_POSTS_FOLDER"), $this->attributes['post_id'], $this->attributes['post_image']);
        } else if (($this->attributes['post_type'] =='Audio' || $this->attributes['post_type'] =='Video') && !empty($this->attributes['post_image'])) {
            $link = getPhotoURL(config("constants.UPLOAD_POSTS_FOLDER"), $this->attributes['post_id'], $this->attributes['post_image']);
        }else{
            $link = url('/public/assets/images/' . config("constants.DEFAULT_PLACEHOLDER_IMAGE"));
        }
        return $link;
    }

    // public function getUImageAttribute() {
    //     $link = '';
    //     if (!empty($this->attributes['u_image'])) {
    //         $link = getPhotoURL(config('constants.UPLOAD_USERS_FOLDER'), $this->attributes['u_id'], $this->attributes['u_image']);
    //     } else {
    //         $link = url('/public/assets/images/' . config("constants.DEFAULT_PLACEHOLDER_IMAGE"));
    //     }
    //     return $link;
    // }

    // public function getCorImageAttribute() {
    //     $link = '';
    //     if (!empty($this->attributes['cor_image'])) {
    //         $link = url('/public/uploads/busUser/'. $this->attributes['u_id'].'/'. $this->attributes['cor_image']);
    //     } else {
    //         $link = url('/public/assets/images/' . config("constants.DEFAULT_PLACEHOLDER_IMAGE"));
    //     }
    //     return $link;
    // }

}
