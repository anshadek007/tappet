<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class EventImages extends Model {

    protected $primaryKey = 'event_image_id';
    protected $table = 'event_images';
    public $timestamps = false;

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
    protected $hidden = [];

    public function getEventImageImageAttribute() {
        $link = '';
        if (!empty($this->attributes['event_image_image'])) {
            $link = getPhotoURL(config("constants.UPLOAD_EVENTS_FOLDER"), $this->attributes['event_image_event_id'], $this->attributes['event_image_image']);
        } else {
            $link = url('/public/assets/images/' . config("constants.DEFAULT_PLACEHOLDER_IMAGE"));
        }
        return $link;
    }

}
