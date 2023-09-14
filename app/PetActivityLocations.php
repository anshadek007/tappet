<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class PetActivityLocations extends Model {

    protected $primaryKey = 'pet_activity_location_id';
    protected $table = 'pet_activity_locations';
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

}
