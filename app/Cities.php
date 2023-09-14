<?php

namespace App;

use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Model;

class Cities extends Model {

    use SoftDeletes;

    protected $primaryKey = 'city_id';
    protected $table = 'cities';

    const CREATED_AT = "city_created_at";
    const UPDATED_AT = "city_updated_at";
    const DELETED_AT = "city_deleted_at";

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

    public function country() {
        return $this->hasOne('App\Countries', "c_id", "city_country_id");
    }
    
    public function tours() {
        return $this->hasMany('App\Tours', "tour_city_id","city_id");
    }

}
