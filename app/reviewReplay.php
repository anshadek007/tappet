<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class reviewReplay extends Model
{
    protected $table = 'review_replays';
    protected $guarded = ['id'];

    public function rateReview(){
        return $this->belongsTo(CorporateRateReview::class, 'review_id');
    }
}
