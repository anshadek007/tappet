<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class ApiLogs extends Model {

    protected $primaryKey = 'al_id';
    protected $table = 'api_logs';

    const CREATED_AT = "al_created_at";
    const UPDATED_AT = "al_updated_at";
    const DELETED_AT = "al_deleted_at";

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
