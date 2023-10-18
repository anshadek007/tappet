<?php

namespace App;
use Laravel\Passport\HasApiTokens;
use Illuminate\Database\Eloquent\Model;

class GuestUser extends Model
{
    use HasApiTokens;
    protected $fillable = ['id','name','email','conversation_id'];
}
