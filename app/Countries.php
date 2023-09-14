<?php

namespace App;

use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Model;

class Countries extends Model {

    use SoftDeletes;

    protected $primaryKey = 'c_id';
    protected $table = 'countries';

    const CREATED_AT = "c_created_at";
    const UPDATED_AT = "c_updated_at";
    const DELETED_AT = "c_deleted_at";

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

    public function cities() {
        return $this->hasMany('App\Cities', "city_country_id","c_id");
    }

}
