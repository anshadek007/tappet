<?php

namespace App;

use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Model;

class PetCollars extends Model {

    use SoftDeletes;

    protected $primaryKey = 'pet_collar_id';
    protected $table = 'pet_collars';

    const CREATED_AT = "pet_collar_created_at";
    const UPDATED_AT = "pet_collar_updated_at";
    const DELETED_AT = "pet_collar_deleted_at";

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
