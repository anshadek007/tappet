<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class UserRolesTypes extends Model {

    use SoftDeletes;

    protected $primaryKey = 'urpt_id';
    protected $table = 'user_role_permission_types';

    const CREATED_AT = "urpt_created_at";
    const UPDATED_AT = "urpt_updated_at";
    const DELETED_AT = "urpt_deleted_at";

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'urpt_name',
        'urpt_urpg_id',
        'urpt_status',
    ];

    public function roleGroup() {
        return $this->hasMany('App\UserRolesGroups', 'urpg_id', 'urpt_urpg_id')->where('urpg_status', '=', 1);
    }

}
