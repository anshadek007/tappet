<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class CorporateServices extends Model {

    protected $primaryKey = 'corporate_service_id';
    protected $table = 'corporate_services';

    const CREATED_AT = "corporate_service_created_at";
    const UPDATED_AT = "corporate_service_updated_at";
    const DELETED_AT = "corporate_service_deleted_at";

    public function getImageAttribute() {
        $link = '';
        if (!empty($this->attributes['image'])) {
            $link = url('/public/service/' .$this->attributes['image']);
        } else {
            $link = url('public/assets/images/no-image-placeholder.jpg');
        }
        return $link;
    }

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
