<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class UserRolesGroups extends Model {

    use SoftDeletes;

    protected $primaryKey = 'urpg_id';
    protected $table = 'user_role_permission_groups';

    const CREATED_AT = "urpg_created_at";
    const UPDATED_AT = "urpg_updated_at";
    const DELETED_AT = "urpg_deleted_at";

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'urpg_name',
        'urpt_status',
    ];

    public function roleTypes() {
        return $this->hasMany('App\UserRolesTypes', "urpt_urpg_id", 'urpg_id')->where('urpt_status', '=', 1);
    }

}
