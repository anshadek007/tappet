<?php

namespace App;

use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Model;

class UserCallHistory extends Model {

    use SoftDeletes;

    protected $primaryKey = 'call_history_id';
    protected $table = 'user_call_histories';

    const CREATED_AT = "call_history_created_at";
    const UPDATED_AT = "call_history_updated_at";
    const DELETED_AT = "call_history_deleted_at";

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
