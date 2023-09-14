<?php

namespace App;

use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Model;

class Events extends Model {

    use SoftDeletes;

    protected $primaryKey = 'event_id';
    protected $table = 'events';

    const CREATED_AT = "event_created_at";
    const UPDATED_AT = "event_updated_at";
    const DELETED_AT = "event_deleted_at";

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
            $event->event_members()->delete();
            $event->event_members()->forceDelete();
            
            $event->event_groups()->delete();
            $event->event_groups()->forceDelete();
            
            $event->images()->delete();
            $event->images()->forceDelete();
            
            $event->event_posts()->delete();
            $event->event_posts()->forceDelete();
        });
    }

    public function addedBy() {
        return $this->hasOne('App\User', "u_id", "event_owner_id");
    }

    public function addedByCor() {
        return $this->hasOne('App\BusinessUser', "u_id", "event_corporate_id");
    }

    public function images() {
        return $this->hasMany('App\EventImages', "event_image_event_id", "event_id");
    }

    public function event_groups() {
        return $this->hasMany('App\EventGroups', "eg_event_id", "event_id");
    }
    
    public function event_has_post() {
        return $this->hasOne('App\Posts', "post_event_id", "event_id");
    }
    
    public function event_posts() {
        return $this->hasMany('App\Posts', "post_event_id", "event_id");
    }

    public function event_members() {
        return $this->hasMany('App\EventMembers', "em_event_id", "event_id");
    }

    public function event_last_two_members() {
        return $this->hasMany('App\EventMembers', "em_event_id", "event_id")->orderBy('em_id', 'DESC')->limit(2);
    }

    public function getEventImageAttribute() {
        $link = '';
        if (!empty($this->attributes['event_image'])) {
            $link = getPhotoURL(config("constants.UPLOAD_EVENTS_FOLDER"), $this->attributes['event_id'], $this->attributes['event_image']);
        } else {
            $link = url('/public/assets/images/' . config("constants.DEFAULT_PLACEHOLDER_IMAGE"));
        }
        return $link;
    }

    public function getEventImageImageAttribute() {
        $link = '';
        if (!empty($this->attributes['event_image_image'])) {
            $link = getPhotoURL(config("constants.UPLOAD_EVENTS_FOLDER"), $this->attributes['event_image_event_id'], $this->attributes['event_image_image']);
        } else {
            $link = url('/public/assets/images/' . config("constants.DEFAULT_PLACEHOLDER_IMAGE"));
        }
        return $link;
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
