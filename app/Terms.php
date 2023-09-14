<?php

namespace App;

use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Model;

class Terms extends Model {

    use SoftDeletes;

    protected $primaryKey = 'tc_id';
    protected $table = 'terms_and_conditions';

    const CREATED_AT = "tc_created_at";
    const UPDATED_AT = "tc_updated_at";
    const DELETED_AT = "tc_deleted_at";

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
