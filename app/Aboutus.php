<?php

namespace App;

use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Model;

class Aboutus extends Model {

    use SoftDeletes;

    protected $primaryKey = 'a_id';
    protected $table = 'aboutus';

    const CREATED_AT = "a_created_at";
    const UPDATED_AT = "a_updated_at";
    const DELETED_AT = "a_deleted_at";

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
