<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class NotificationsData extends Model {

    protected $primaryKey = 'nd_id';

    const CREATED_AT = "nd_created_at";
    const UPDATED_AT = "nd_updated_at";

    protected $fillable = [];
    protected $guarded = [];

}
