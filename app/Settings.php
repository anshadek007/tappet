<?php

namespace App;

use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Model;

class Settings extends Model {

    use SoftDeletes;

    protected $primaryKey = 's_id';
    protected $table = 'settings';

    const CREATED_AT = "s_created_at";
    const UPDATED_AT = "s_updated_at";
    const DELETED_AT = "s_deleted_at";

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

    /**
     * This function get all the reports data with pagination and filter
     * 
     * @return type
     */
    public function get_settings() {

        $where = array(
            's_id' => 1,
            "s_status" => 1
        );

        $settingData = $this->get_all_rows($this->setting_table, "s_name,s_value,s_type", $where);
        $settingArray = array();

        if (!empty($settingData)) {
            foreach ($settingData as $data) {
                if ($data['s_type'] == 1) {
                    $settingArray[$data['s_name']] = $data['s_value'];
                } else {
                    $settingArray[$data['s_name']] = json_decode($data['s_value'], true);
                }
            }
        }
        //echo "<pre>";print_r($settingData);exit;

        return $settingArray;
    }

    /**
     * This function is used for get settings value
     * 
     * @return array
     */
    public function get_setting_by_key($key = '') {
        $return_value = "";
        if (!empty($key)) {

            $where = array(
                's_name' => $key,
                "s_status" => 1
            );

            $data = $this->get_single_row($this->setting_table, "s_name,s_value,s_type", $where);

            $return_value = "";
            if (!empty($data)) {
                if ($data['s_type'] == 1) {
                    $return_value = $data['s_value'];
                } elseif ($data['s_type'] == 2) {
                    $return_value = json_decode($data['s_value'], true);
                } elseif ($data['s_type'] == 3) {
                    if (!empty($data['s_value'])) {
                        if (file_exists(UPLOAD_REL_PATH . UPLOAD_SETTINGS_FOLDER . '/' . $data['s_value'])) {
                            $image_url = UPLOAD_REL_PATH . UPLOAD_SETTINGS_FOLDER . '/' . $data['s_value'];
                        } else {
                            $image_url = DEFAULT_USER_IMAGE_ABS;
                        }
                    } else {
                        $image_url = DEFAULT_USER_IMAGE_ABS;
                    }

                    $return_value = $image_url;
                } else {
                    $return_value = $data['s_value'];
                }
            }
        }

        return $return_value;
    }

}
