<?php

namespace App;

use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Model;

class UserFavourite extends Model
{
    use SoftDeletes;

    protected $primaryKey = 'uf_id';
    protected $table = 'users_favourite';

    const CREATED_AT = "uf_created_at";
    const UPDATED_AT = "uf_updated_at";
    const DELETED_AT = "uf_deleted_at";
    
    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [];
    protected $guarded = [];

}
