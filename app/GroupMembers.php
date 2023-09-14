<?php

namespace App;

use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Model;

class GroupMembers extends Model {

    use SoftDeletes;

    protected $primaryKey = 'gm_id';
    protected $table = 'group_members';

    const CREATED_AT = "gm_created_at";
    const UPDATED_AT = "gm_updated_at";
    const DELETED_AT = "gm_deleted_at";

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

    public function member() {
        return $this->hasOne('App\User', "u_id", "gm_user_id");
    }
    
    public function groups() {
        return $this->belongsTo('App\Groups', "group_id", "gm_group_id");
    }

//    public function total_pets() {
//        return $this->hasMany("App\Pets", "pet_owner_id", "u_id");
//    }

}
