<?php

namespace App;

use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class Guest extends Authenticatable
{
    use Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $primaryKey = 'userid';
    protected $table ="guest";
    protected $fillable = ["userid","name","last_name","email","password","phone","gender","device_token","profile","latitude","longitude","status","created_at","updated_at","photo_id","device_type","device_id","api_token","freinds_agegroup","visible_map","id_proof","idproof_aproved","invite_friend_count","tot_invities","country","state","city","postal_code","is_active","reason_doc_reject","firebase_id","username","customer_stripId","dob","user_block_count","no_of_user_blocked","is_user_preference","real_freind_count","tot_fav_venue","banner_click","booking_count","tot_memory_posted","tot_featured_brand","tot_venue_tag","is_idproof"         
    ];

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
}
