<?php

namespace App;

use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Model;

class PetBreeds extends Model {

    use SoftDeletes;

    protected $primaryKey = 'pb_id';
    protected $table = 'pet_breeds';

    const CREATED_AT = "pb_created_at";
    const UPDATED_AT = "pb_updated_at";
    const DELETED_AT = "pb_deleted_at";

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
