<?php

namespace App;

use Illuminate\Notifications\Notifiable;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Laravel\Passport\HasApiTokens;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;
use Illuminate\Contracts\Auth\CanResetPassword;
use App\Notifications\ResetPassword as ResetPasswordNotification;
use App\Pets;
use App\PetBreeds;
use App\CorporateServicesOffered;

class User extends Authenticatable {

    use HasApiTokens,
        Notifiable,
        SoftDeletes;

    protected $primaryKey = 'u_id';
    protected $table = 'users';

    const CREATED_AT = "u_created_at";
    const UPDATED_AT = "u_updated_at";
    const DELETED_AT = "u_deleted_at";

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
        return $this->password;
    }

    public function getEmailForPasswordReset() {
        return $this->email;
    }

    public function routeNotificationForMail()
    {
        return $this->u_email;
    }

    public function routeNotificationFor($driver) {
        if (method_exists($this, $method = 'routeNotificationFor' . Str::studly($driver))) {
            return $this->{$method}();
        }

        switch ($driver) {
            case 'database':
                return $this->notifications();
            case 'mail':
                return $this->u_email;
            case 'nexmo':
                return $this->phone_number;
        }
    }

    public function getUserRole() {
        return $this->hasOne("App\UserRoles", "role_id", "u_role_id");
    }

    public function getChilds($user_id) {
        $userChilds = User::whereRaw('FIND_IN_SET(' . $user_id . ',u_parent_users_id)')
                ->selectRaw('GROUP_CONCAT(u_id) as childs')
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
            $parent_users_list = User::whereIn("u_role_id", $parent_role_ids)
                    ->where("u_status", 1)
                    ->select("u_id", \DB::raw("CONCAT(u_first_name,' ',u_last_name) as user_name"))
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

            $all_employees = User::where("u_status", 1)
                    ->select("u_id", \DB::raw("CONCAT(u_first_name,' ',u_last_name) as user_name"))
                    ->where("u_id", "!=", $user_id)
                    ->where("u_user_type", 1)
                    ->get();

            foreach ($all_employees as $employee) {
                $user_list[$employee['u_id']] = $employee['u_user_name'];
            }
        }

        return $user_list;
    }

    public function addedBy() {
        return $this->hasOne('App\User', "u_id", "u_added_by_u_id");
    }

    public function validateUser($id) {
        $user = User::select("*", \DB::raw("CONCAT(u_first_name,' ',u_last_name) as user_name"))
                ->where('u_id', $id)
                ->where('u_status', 1)
                ->first();
        if (!$user) {
            return false;
        }
        return $user;
    }

    /**
     * Get Current user details by user id
     * 
     * @param type $id
     * @return boolean
     */
    public function getAuthUser($id) {
        $user = User::select("u_first_name", "u_last_name", "u_email", "u_mobile_number", "u_image", "u_created_at as u_date", "u_country", "u_state", "u_city", "u_latitude", "u_longitude", "u_country_code", "u_created_at")->where('u_id', $id)->where('u_status', 1)->first();
        if (!$user) {
            return false;
        }

        $user->u_date = strval(strtotime($user->u_date));
        $user->total_rating = 0;
        $user->rating = 0;

        //Get User total ratings and average ratings
        $get_userrating = \App\UserRatings::select("ur_rating")->where("ur_rating_to", $id)->get();
        if (count($get_userrating) > 0) {

            $total_rating = 0;
            foreach ($get_userrating as $userrating) {

                $total_rating += $userrating->ur_rating;
            }

            $avg_rating = ($total_rating / count($get_userrating));

            $user->total_rating = $total_rating;
            $user->rating = $avg_rating;
        }

        $user->u_image = str_replace(url('/public/uploads/') . "/", "", getPhotoURL('users', $id, $user->u_image));
        return $user;
    }

    public function sendPasswordResetNotification($token) {

        $this->notify(new ResetPasswordNotification($token));
    }

    public function city() {
        return $this->hasOne("App\Cities", "city_id", "u_city");
    }

    public function country() {
        return $this->hasOne("App\Countries", "c_id", "u_country");
    }

    public function user_city_country($user_id = 0) {
        return User::select("u_id", "u_first_name", "u_last_name", "u_email", "u_image", "city_name", "c_name")
                        ->leftJoin('cities', 'city_id', 'u_city')
                        ->leftJoin('countries', 'c_id', 'city_country_id')
                        ->where('u_id', $user_id)->first();
    }

    /**
     * 
     * @param type $id
     */
    public function friends($id = 0) {
        $get_all_records = User::select(
                        "u_id", "u_first_name", "u_last_name", "u_email", "u_image", "city_name", "c_name", "ufr_status"
                )
                ->leftJoin('user_friends', function ($join) {
                    $join->on('u_id', '=', 'ufr_invited_user_id')
                    ->orOn('u_id', '=', 'ufr_user_id');
                })
                ->leftJoin('cities', 'city_id', '=', 'u_city')
                ->leftJoin('countries', 'c_id', '=', 'city_country_id')
                ->where(function($query) use($id) {
                    $query->where('ufr_user_id', $id)
                    ->orWhere('ufr_invited_user_id', $id);
                })
                ->where('u_status', "!=", 9)
                ->where('ufr_status', "!=", 9)
                ->where('u_id', "!=", $id);

        return $get_all_records;
    }

    public function has_total_friends_count($id = 0) {
        $fetch_record = User::select("*")
                ->leftJoin('user_friends', function ($join) {
                    $join->on('u_id', '=', 'ufr_invited_user_id')
                    ->orOn('u_id', '=', 'ufr_user_id');
                })
                ->where(function($query) use($id) {
                    $query->where('ufr_user_id', $id)
                    ->orWhere('ufr_invited_user_id', $id);
                })
                ->where('u_status', "!=", 9)
                ->where('ufr_status', 1)
                ->where('u_id', "!=", $id);

        return (int) $fetch_record->count();
    }

    /**
     * 
     * @param type $id
     * @param type $invited_user_id
     * @return type
     */
    public function find_mutual_friends($id = 0, $invited_user_id = 0) {
        $total_mutual_friends = 0;

        if (!empty($id) && !empty($invited_user_id)) {
            $find_mutual = 'SELECT UserAFriends.UserId FROM
                                (
                                  SELECT ufr_invited_user_id UserId FROM tappet_user_friends WHERE ufr_user_id = ' . $id . '
                                    UNION 
                                  SELECT ufr_user_id UserId FROM tappet_user_friends WHERE ufr_invited_user_id = ' . $id . '
                                ) AS UserAFriends
                                JOIN  
                                (
                                  SELECT ufr_invited_user_id UserId FROM tappet_user_friends WHERE ufr_user_id = ' . $invited_user_id . '
                                    UNION 
                                  SELECT ufr_user_id UserId FROM tappet_user_friends WHERE ufr_invited_user_id = ' . $invited_user_id . '
                                ) AS UserBFriends 
                                ON  UserAFriends.UserId = UserBFriends.UserId';

            $check_mutual_friend = \DB::select($find_mutual);


            if (!empty($check_mutual_friend) && count($check_mutual_friend) > 0) {
                $total_mutual_friends = count($check_mutual_friend);
            }
        }

        return (int) $total_mutual_friends;
    }

    public function get_mutual_friends_list($id = 0, $invited_user_id = 0) {
        $check_mutual_friend = [];

        if (!empty($id) && !empty($invited_user_id)) {
            $find_mutual = 'SELECT UserAFriends.UserId FROM
                                (
                                  SELECT ufr_invited_user_id UserId FROM tappet_user_friends WHERE ufr_user_id = ' . $id . '
                                    UNION 
                                  SELECT ufr_user_id UserId FROM tappet_user_friends WHERE ufr_invited_user_id = ' . $id . '
                                ) AS UserAFriends
                                JOIN  
                                (
                                  SELECT ufr_invited_user_id UserId FROM tappet_user_friends WHERE ufr_user_id = ' . $invited_user_id . '
                                    UNION 
                                  SELECT ufr_user_id UserId FROM tappet_user_friends WHERE ufr_invited_user_id = ' . $invited_user_id . '
                                ) AS UserBFriends 
                                ON  UserAFriends.UserId = UserBFriends.UserId';

            $check_mutual_friend = \DB::select($find_mutual);
        }

        return $check_mutual_friend;
    }

    public function pets($id = 0) {
        $get_all_records = Pets::with(['images','co_owners','co_owners.member'])->select("*")
                ->leftJoin('pet_types', function ($join) {
                    $join->on('pt_id', '=', 'pet_type_id');
                })
                ->where('pet_owner_id', $id);

        return $get_all_records;
    }
    
    public function pet_co_owned($id = 0) {
        $get_all_records = Pets::with(['images'])->select("*")
                ->leftJoin('pet_types', function ($join) {
                    $join->on('pt_id', '=', 'pet_type_id');
                })
                ->leftJoin('pet_co_owners', function ($join) {
                    $join->on('pet_id', '=', 'pet_co_owner_pet_id');
                })
                ->where('pet_co_owner_owner_id', $id)
                ->groupBy('pet_id');

        return $get_all_records;
    }

    public function getBreed($pet_breed_ids) {
        $data = PetBreeds::whereRaw('FIND_IN_SET(pb_id,"' . $pet_breed_ids . '")')
                ->select('*')
                ->get()
                ->toArray();

        return $data;
    }

    public function getUImageAttribute() {
        $link = '';
        if (!empty($this->attributes['u_image'])) {
            $link = getPhotoURL(config('constants.UPLOAD_USERS_FOLDER'), $this->attributes['u_id'], $this->attributes['u_image']);
        } else {
            $link = url('/public/assets/images/' . config("constants.DEFAULT_PLACEHOLDER_IMAGE"));
        }
        return $link;
    }
    
    public function getUCoverPhotoAttribute() {
        $link = '';
        if (!empty($this->attributes['u_cover_photo'])) {
            $link = getPhotoURL(config('constants.UPLOAD_USERS_FOLDER'), $this->attributes['u_id'], $this->attributes['u_cover_photo']);
        } else {
            $link = url('/public/assets/images/' . config("constants.DEFAULT_PLACEHOLDER_IMAGE"));
        }
        return $link;
    }

    public function total_pets_count() {
        return $this->hasMany("App\Pets", "pet_owner_id", "u_id");
    }

    public function user_pets() {
        return $this->hasMany('App\Pets', "pet_owner_id", "u_id")->orderBy('pet_id', 'DESC');
    }

    public function user_groups() {
        return $this->hasMany('App\Groups', "group_owner_id", "u_id")->orderBy('group_id', 'DESC');
    }

    public function user_events() {
        return $this->hasMany('App\Events', "event_owner_id", "u_id")->orderBy('event_start_date', 'DESC');
    }

    public function user_posts() {
        return $this->hasMany('App\Posts', "post_owner_id", "u_id")->orderBy('post_id', 'DESC');
    }

    public function user_devices() {
        return $this->hasMany('App\UserDeviceToken', "udt_u_id", "u_id")->orderBy('udt_updated_at', 'DESC');
    }

    public function coruser_devices() {
        return $this->hasMany('App\UserDeviceToken', "cor_uid", "u_id")->orderBy('udt_updated_at', 'DESC');
    }

    public function user_blocked() {
        return $this->hasMany('App\UserBlocks', "user_block_user_id", "u_id");
    }

    public function offered_service() {
        return $this->hasMany('App\CorporateServicesOffered', "corporate_services_offered_owner_id", "u_id");
    }

    public function my_services($id = null) {
        $get_all_records = CorporateServicesOffered::select("corporate_service_id","corporate_service_name")
                ->leftJoin('corporate_services', function ($join) {
                    $join->on('corporate_services_offered_service_id', '=', 'corporate_service_id');
                })
                ->where('corporate_services_offered_owner_id', $id)
                ->get();

        return $get_all_records;
    }

}
