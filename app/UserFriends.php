<?php

namespace App;

use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Model;

class UserFriends extends Model {

    use SoftDeletes;

    protected $primaryKey = 'ufr_id';
    protected $table = 'user_friends';

    const CREATED_AT = "ufr_created_at";
    const UPDATED_AT = "ufr_updated_at";
    const DELETED_AT = "ufr_deleted_at";

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
