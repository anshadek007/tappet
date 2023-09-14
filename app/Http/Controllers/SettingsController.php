<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Settings;

class SettingsController extends Controller {

    protected $route_name;
    protected $module_singular_name;
    protected $module_plural_name;
    private $keys = array('organization_closing_time', 's1');

    public function __construct() {
        $this->route_name = 'settings';
        $this->module_singular_name = 'Setting';
        $this->module_plural_name = 'Settings';
        $this->middleware("checkmodulepermission");
    }

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index() {
        $settings = Settings::where('s_status', 1)->get();
        return view($this->route_name . ".index", compact('settings'));
    }

    /**
     * Update the specified resource in storage.
     *
     * 1. Textbox
     * 2. Textarea
     * 3. Selectbox
     * 4. Checkbox
     * 5. Radio
     * 6. File
     * 7. Number
     * 8. JSON
     * 
     * 
     * @param  \Illuminate\Http\Request  $request
     * @param  Settings  $setting
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request) {
        $filtered_request_array = $request->all();
        unset($filtered_request_array['_token']);
//        dd($filtered_request_array);

        foreach ($filtered_request_array as $key => $value) {
            $insert_array = array();
            $key_array = explode("_", $key);
            $filed_type = end($key_array);
            array_pop($key_array);
            $key_name = implode("_", $key_array);
            if (!empty($filed_type)) {
                if ($filed_type == 4) {
                    $data_setting = implode(',', $value);
                    $insert_array = array(
                        's_value' => $data_setting,
                    );
                } elseif ($filed_type == 6) {
                    if (!empty($_FILES[$key]['name']) && $_FILES[$key]['error'] == 0) {
                        $upload_path = UPLOAD_REL_PATH . "/" . UPLOAD_SETTINGS_FOLDER;
                        $upload_folder = UPLOAD_SETTINGS_FOLDER;
                        $uploaded_file_names = do_upload_multiple($upload_path, $_FILES, $upload_folder);
                        //echo "<pre>";print_r($uploaded_file_names);exit;
                        if (!empty($uploaded_file_names[$key])) {
                            $insert_array = array(
                                's_value' => $uploaded_file_names[$key],
                            );
                        }
                    }
                } else if ($filed_type == 8) {
                    $data_setting = json_encode($value);
                    $insert_array = array(
                        's_value' => $data_setting,
                    );
                } else {
                    $insert_array = array(
                        's_value' => trim($value),
                    );
                }
            } else {
                $insert_array = array(
                    's_value' => trim($value),
                );
            }

            if (!empty($insert_array)) {
                $setting = Settings::where('s_name', $key_name)->first();

                foreach ($insert_array as $ins_key => $insert_arr) {
                    $setting->{$ins_key} = $insert_arr;
                }
                $setting->save();
            }
        }
//
//        if (!empty($_FILES)) {
//            $upload_path = UPLOAD_REL_PATH . "/" . UPLOAD_SETTINGS_FOLDER;
//            $upload_folder = UPLOAD_SETTINGS_FOLDER;
//            $uploaded_file_names = do_upload_multiple($upload_path, $_FILES, $upload_folder);
//
//            foreach ($_FILES as $key => $value) {
//                $insert_array = array();
//                $key_array = explode("_", $key);
//                $filed_type = end($key_array);
//                array_pop($key_array);
//                $key_name = implode("_", $key_array);
//
//                if (!empty($uploaded_file_names[$key])) {
//                    $insert_array = array(
//                        's_value' => $uploaded_file_names[$key],
//                    );
//                    $where = array(
//                        's_name' => $key_name
//                    );
//
//                    $this->Common_model->update(TBL_SETTINGS, $insert_array, $where);
//                }
//            }
//        }
        
        return redirect()->route($this->route_name . ".index")->with("success", $this->module_singular_name . " Update Successfully");
    }

}
