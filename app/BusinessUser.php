<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Passport\HasApiTokens;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use App\Notifications\CorporateResetPassword as ResetPasswordNotification;
use Illuminate\Contracts\Auth\CanResetPassword;

class BusinessUser extends Authenticatable
{
    use HasApiTokens,Notifiable;

    protected $table = 'business_user';
    protected $guarded = ['u_id'];
    protected $primaryKey = 'u_id';

    const CREATED_AT = "u_created_at";
    const UPDATED_AT = "u_updated_at";
    const DELETED_AT = "u_deleted_at";

    public function validateUser($id) {
        $user = BusinessUser::select("*", \DB::raw("CONCAT(u_first_name,' ',u_last_name) as user_name"))
                ->where('u_id', $id)
                ->where('u_status', 1)
                ->with('user_devices')
                ->first();
        if (!$user) {
            return false;
        }
        return $user;
    }

    public function services(){
        return $this->hasMany(CorporateServicesOffered::class, 'corporate_services_offered_owner_id', 'u_id');
    }
    
    public function follwer(){
        return $this->hasMany(CorporateFollwer::class, 'cor_u_id', 'u_id');
    }

    public function rateReview(){
        return $this->hasMany(CorporateRateReview::class, 'cor_u_id', 'u_id');
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

    public function getUImageAttribute() {
        $link = '';
        if (!empty($this->attributes['u_image'])) {
            $link = url('/public/uploads/busUser/'. $this->attributes['u_id'].'/'. $this->attributes['u_image']);
        } else {
            $link = url('/public/assets/images/' . config("constants.DEFAULT_PLACEHOLDER_IMAGE"));
        }
        return $link;
    }
    
    public function getUCoverPhotoAttribute() {
        $link = '';
        if (!empty($this->attributes['u_cover_photo'])) {
            $link = url('/public/uploads/busUser/'. $this->attributes['u_id'].'/'. $this->attributes['u_cover_photo']);
        } else {
            $link = url('/public/assets/images/' . config("constants.DEFAULT_PLACEHOLDER_IMAGE"));
        }
        return $link;
    }

    public function routeNotificationForMail()
    {
        return $this->u_email;
    }

    public function user_devices() {
        return $this->hasMany('App\UserDeviceToken', "cor_uid", "u_id")->orderBy('udt_updated_at', 'DESC');
    }

    public function sendPasswordResetNotification($token)
    {
        $this->notify(new ResetPasswordNotification($token));
    }

    public function getBreed($pet_breed_ids) {
        $data = PetBreeds::whereRaw('FIND_IN_SET(pb_id,"' . $pet_breed_ids . '")')
                ->select('*')
                ->get()
                ->toArray();

        return $data;
    }
}
