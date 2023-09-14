<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class CorporateRateReview extends Model
{
    protected $table = 'corporate_rate_reviews';
    protected $guarded = ['id'];

    public function user(){
        return $this->belongsTo(User::class, 'u_id', 'u_id');
    }

    public function replay(){
        return $this->hasOne(reviewReplay::class, 'review_id');
    }

    public function getTagAttribute() {
        $tag = '';
        if (!empty($this->attributes['tag'])) {
            $tag = explode(',', $this->attributes['tag']);
        } else {
            $tag = '';
        }
        return $tag;
    }
}
