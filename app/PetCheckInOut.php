<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class PetCheckInOut extends Model
{
    protected $table = 'pet_check_in_out';
    protected $guarded = ['id'];

    public function pet() {
        return $this->hasOne('App\Pets', "pet_id", "pet_id");
    }

}
