<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class CorporateServicesOffered extends Model {

    protected $primaryKey = 'corporate_services_offered_id';
    protected $table = 'corporate_services_offereds';
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

    public function service() {
        return $this->hasOne('App\CorporateServices', "corporate_service_id", "corporate_services_offered_service_id");
    }

}
