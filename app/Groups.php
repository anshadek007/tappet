<?php

namespace App;

use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Model;

class Groups extends Model {

    use SoftDeletes;

    protected $primaryKey = 'group_id';
    protected $table = 'groups';

    const CREATED_AT = "group_created_at";
    const UPDATED_AT = "group_updated_at";
    const DELETED_AT = "group_deleted_at";

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

    public function addedBy() {
        return $this->hasOne('App\User', "u_id", "group_owner_id");
    }

    public function group_members() {
        return $this->hasMany('App\GroupMembers', "gm_group_id", "group_id");
    }

    public function group_events() {
        return $this->hasMany('App\EventGroups', "eg_group_id", "group_id");
    }

    public function group_last_two_members() {
//        return $this->hasMany('App\GroupMembers', "gm_group_id", "group_id");
//        return $this->hasMany('App\GroupMembers', "gm_group_id", "group_id")->orderBy('gm_id', 'DESC')->limit(2);
        return $this->hasMany('App\GroupMembers', "gm_group_id", "group_id")->orderBy('gm_id', 'DESC');
    }

    public function group_last_two_only() {
//        return $this->hasMany('App\GroupMembers', "gm_group_id", "group_id");
        return $this->hasMany('App\GroupMembers', "gm_group_id", "group_id")->orderBy('gm_id', 'DESC')->limit(2);
//        return $this->hasMany('App\GroupMembers', "gm_group_id", "group_id")->orderBy('gm_id', 'DESC');
    }

    public function getGroupImageAttribute() {
        $link = '';
        if (!empty($this->attributes['group_image'])) {
            $link = getPhotoURL(config("constants.UPLOAD_GROUPS_FOLDER"), $this->attributes['group_id'], $this->attributes['group_image']);
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
