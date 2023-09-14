<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Support\Facades\DB;
use App\Jobs\Job;
use Illuminate\Support\Facades\Storage;
use App\AdvertisementImages;

class AdvertisementImageUpload implements ShouldQueue {

    use Dispatchable,
        InteractsWithQueue,
        Queueable,
        SerializesModels;

    public $imagesArray;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct($imagesArray) {
        $this->imagesArray = $imagesArray;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle() {
        
        $decode_data = json_decode($this->imagesArray, TRUE);
        
        if (!empty($decode_data)) {

            foreach ($decode_data as $image){
                $adv_id = $image['adv_id'];
                $image_path =  $image['sourcefilePath'];
                $name = $image['image_name'];
                $filePath = '/SouqBHApp/'.$adv_id."/".$name;
                
                try{
                    $x= Storage::disk('s3')->put($filePath, file_get_contents($image_path),'public');
                    
                    if($x == 1)
                    {
                        $new_img = new AdvertisementImages();
                        $new_img->ai_image = $name;
                        $new_img->ai_advt_id = $adv_id;
                        $new_img->save();
                    }
                                        
                } catch (Exception $e) {
                    $s3log_path = storage_path()."/logs/s3upload.log";
                    $exception = $e->getMessage();
                    file_put_contents($s3log_path, "\n ================================================ \n\n", FILE_APPEND | LOCK_EX);
                    file_put_contents($s3log_path, "\n notification params : \n " . $exception . " \n\n", FILE_APPEND | LOCK_EX);
                }
            }

        }
    }

}
