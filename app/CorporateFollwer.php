<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class CorporateFollwer extends Model
{
    protected $table = 'corporate_follwers';
    protected $guarded = ['id'];

    public function user(){
        return $this->belongsTo(User::class, 'u_id', 'u_id');
    }
    public function coUser(){
        return $this->belongsTo(BusinessUser::class, 'cor_u_id', 'u_id');
    }

}
