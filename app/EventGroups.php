<?php

namespace App;

use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Model;

class EventGroups extends Model {

    use SoftDeletes;

    protected $primaryKey = 'eg_id';
    protected $table = 'event_groups';

    const CREATED_AT = "eg_created_at";
    const UPDATED_AT = "eg_updated_at";
    const DELETED_AT = "eg_deleted_at";

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

    public function event() {
        return $this->hasOne('App\Events', "event_id", "eg_event_id");
    }

    public function group() {
        return $this->hasOne('App\Groups', "group_id", "eg_group_id");
    }

}
