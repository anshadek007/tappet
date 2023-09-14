<?php

namespace App;

use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Model;

class PetActivities extends Model {

    use SoftDeletes;

    protected $primaryKey = 'pet_activity_id';
    protected $table = 'pet_activities';

    const CREATED_AT = "pet_activity_created_at";
    const UPDATED_AT = "pet_activity_updated_at";
    const DELETED_AT = "pet_activity_deleted_at";

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

    public function activity_locations() {
        return $this->hasMany('App\PetActivityLocations', "pet_activity_location_activity_id", "pet_activity_id");
    }

}
