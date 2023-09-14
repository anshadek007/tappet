<?php

namespace App;

use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Model;

class Feedback extends Model {

    use SoftDeletes;

    protected $primaryKey = 'f_id';
    protected $table = 'feedback';

    const CREATED_AT = "f_created_at";
    const UPDATED_AT = "f_updated_at";
    const DELETED_AT = "f_deleted_at";

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
