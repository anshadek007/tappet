<?php

namespace App;

use Illuminate\Notifications\Notifiable;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;
use Illuminate\Contracts\Auth\CanResetPassword;
use Illuminate\Auth\Notifications\ResetPassword;

class Admins extends Authenticatable {

    use Notifiable,
        SoftDeletes;

    protected $primaryKey = 'a_id';
    protected $table = 'admins';
    protected $guard = 'admin';

    const CREATED_AT = "a_created_at";
    const UPDATED_AT = "a_updated_at";
    const DELETED_AT = "a_deleted_at";

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
    protected $hidden = [
        'password', 'remember_token',
    ];

    /**
     * The attributes that should be cast to native types.
     *
     * @var array
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
    ];

    public function getAuthPassword() {
        return $this->a_password;
    }

    public function getEmailForPasswordReset() {
        return $this->a_email;
    }

    public function routeNotificationFor($driver) {
        if (method_exists($this, $method = 'routeNotificationFor' . Str::studly($driver))) {
            return $this->{$method}();
        }

        switch ($driver) {
            case 'database':
                return $this->notifications();
            case 'mail':
                return $this->a_email;
            case 'nexmo':
                return $this->phone_number;
        }
    }

    /**
     * Send the password reset notification.
     *
     * @param  string  $token
     * @return void
     */
    public function sendPasswordResetNotification($token) {
        $this->notify(new ResetPassword($token . '/?a_email=' . $this->a_email));
    }

    public function getUserRole() {
        return $this->hasOne("App\UserRoles", "role_id", "a_role_id");
    }

    public function getChilds($user_id) {
        $userChilds = Admins::whereRaw('FIND_IN_SET(' . $user_id . ',a_parent_users_id)')
                ->selectRaw('GROUP_CONCAT(a_id) as childs')
                ->get()
                ->toArray();

        return rtrim($userChilds[0]['childs'], ",");
    }

    static function get_parent_users_by_role_id($role_id) {
        $get_parent_role_list = \DB::select("
                SELECT  
                    role_parent_role_id,
                    GROUP_CONCAT(@id :=(SELECT role_parent_role_id FROM user_roles WHERE role_id = @id)) AS role_parent_role_id
                FROM(SELECT  @id := '" . $role_id . "') vars
                STRAIGHT_JOIN user_roles
                WHERE role_parent_role_id IS NOT NULL
            ");

        $parent_users_list = array();
        if (!empty($get_parent_role_list[0])) {
            $parent_role_ids = explode(",", $get_parent_role_list[0]->role_parent_role_id);
            $parent_users_list = Admins::whereIn("a_role_id", $parent_role_ids)
                    ->where("a_status", 1)
                    ->select("a_id", \DB::raw("CONCAT(a_first_name,' ',a_last_name) as user_name"))
                    ->get();
        }


        return $parent_users_list;
    }

    static function get_list_of_users($role_id, $user_id) {
        $role_type_by_role_id = \App\UserRoles::where("role_id", $role_id)
                ->select("role_type")
                ->first();

        $user_list = array(
            "" => "Select User"
        );

        if ($role_type_by_role_id) {

            $all_employees = Admins::where("a_status", 1)
                    ->select("a_id", "a_first_name", "a_last_name")
                    ->where("a_id", "!=", $user_id)
                    ->where("a_user_type", 1)
                    ->get();

            foreach ($all_employees as $employee) {
                $user_list[$employee['a_id']] = $employee['a_first_name'] . " " . $employee['a_last_name'];
            }
        }

        return $user_list;
    }

    public function addedBy() {
        return $this->hasOne('App\Admins', "a_id", "a_added_by_u_id");
    }

}
