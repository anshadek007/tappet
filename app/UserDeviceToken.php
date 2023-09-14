<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class UserDeviceToken extends Model {

    const CREATED_AT = 'udt_created_at';
    const UPDATED_AT = 'udt_updated_at';

    protected $primaryKey = "udt_id";
    protected $table = 'user_device_tokens';

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
