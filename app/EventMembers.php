<?php

namespace App;

use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Model;

class EventMembers extends Model {

    use SoftDeletes;

    protected $primaryKey = 'em_id';
    protected $table = 'event_members';

    const CREATED_AT = "em_created_at";
    const UPDATED_AT = "em_updated_at";
    const DELETED_AT = "em_deleted_at";

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
        return $this->hasOne('App\User', "u_id", "em_user_id");
    }

    public function corporatemember() {
        return $this->hasOne('App\BusinessUser', "u_id", "em_user_id");
    }

}
