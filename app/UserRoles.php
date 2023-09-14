<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class UserRoles extends Model {

    use SoftDeletes;

    protected $primaryKey = 'role_id';
    protected $table = 'user_roles';

    const CREATED_AT = "role_created_at";
    const UPDATED_AT = "role_updated_at";
    const DELETED_AT = "role_deleted_at";
    

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'role_type',
        'role_name',
        'role_permissions',
        'role_added_by_u_id',
        'role_parent_role_id',
        'role_status',
    ];

    public function addedBy() {
        return $this->hasOne('App\Admins', "a_id", "role_added_by_u_id");
    }

    public function parentRole() {
        return $this->hasOne('App\UserRoles', "role_id", 'role_parent_role_id');
    }

}
