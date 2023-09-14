<?php

namespace App;

use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Model;

class PetSchedules extends Model {

    use SoftDeletes;

    protected $primaryKey = 'pet_schedule_id';
    protected $table = 'pet_schedules';

    const CREATED_AT = "pet_schedule_created_at";
    const UPDATED_AT = "pet_schedule_updated_at";
    const DELETED_AT = "pet_schedule_deleted_at";

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
