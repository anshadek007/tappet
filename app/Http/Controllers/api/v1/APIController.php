<?php

namespace App\Http\Controllers\api\v1;

use Illuminate\Support\Arr;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Request;
use Image;
use File;
use Illuminate\Support\Facades\DB;
use Illuminate\Foundation\Bus\DispatchesJobs;
use App\Traits\PushNotifications;
use App\Settings;
use App\User;
use Illuminate\Support\Facades\Log;

class APIController extends Controller {

    use DispatchesJobs,
        PushNotifications;

    /**
     * Create a new api_controller instance.
     *
     * @return void
     */
    public function __construct() {
        //
    }

    /**
     * Respond with success
     *
     * @param  array  $response
     * @return array
     */
    protected function respond($response) {
        return array_merge(['result' => $response], ['message' => '', 'status' => true, 'code' => 0]);
    }

    /**
     * Respond with error
     *
     * @param  array  $errors
     * @return array
     */
    protected function respondWithError($errors) {
        return array_merge(['result' => (object) null], ['message' => $errors, 'status' => false, 'code' => 60]);
    }

    /**
     * 
     * @param type $result
     * @param type $message
     * @param type $status
     * @param type $code
     * @return type
     */
    protected function respondResult($result = null, $message = "", $status = true, $code = 0) {
       
        return array_merge(['result' => !empty($result) ? $result : (object) null], ['message' => $message, 'status' => $status, 'code' => $code]);
    }

    protected function respondResultWithEmptyArray($result = null, $message = "", $status = true, $code = 0) {
        return array_merge(['result' => !empty($result) ? $result : array()], ['message' => $message, 'status' => $status, 'code' => $code]);
    }

    /**
     * 
     * @param type $file
     * @param type $id
     * @param type $upload_folder
     */
    protected function uploadFile($file, $id, $upload_folder) {
       
        if (!empty($file) && !empty($id) && is_numeric($id)) {
            $fileName = $file->getClientOriginalName();
            $fileName = rand(0000,9999)."_".time() . '.' . $file->getClientOriginalExtension();
           
            //$fileName = time() . '_' . $this->normalizeString($fileName);
            $file_type = self::filetype($fileName);
            $filePath = upload_path($upload_folder . '/' . $id . '/');
         

            if (!\File::exists($filePath)) {
                \File::makeDirectory($filePath, 0777, true, true);
            }

            if ($file_type == 'image') {
                $img = Image::make($file->getRealPath());
              
                //$img->resize(360, 480, function ($constraint) {
                //$constraint->aspectRatio();
                //});
                $img->save($filePath . $fileName, 100);
            } else {
                $file->move($filePath, $fileName);
            }
            

            return $fileName;
        }
        return false;
    }

    public function normalizeString($str = '') {
        $str = preg_replace('/[\r\n\t ]+/', ' ', $str);
        $str = preg_replace('/[\"\*\/\:\<\>\?\'\|]+/', ' ', $str);
        $str = strtolower($str);
        $str = html_entity_decode($str, ENT_QUOTES, "utf-8");
        $str = htmlentities($str, ENT_QUOTES, "utf-8");
        $str = preg_replace("/(&)([a-z])([a-z]+;)/i", '$2', $str);
        $str = str_replace(' ', '_', $str);
        $str = preg_replace('/[^a-zA-Z0-9_.]/', '', $str);
        $str = rawurlencode($str);
        $str = str_replace('%', '_', $str);
        return $str;
    }

    public function filetype($filename) {
        $fileextension = strtolower(File::extension($filename));
        switch ($fileextension) {
            case 'mp3':
                return 'audio';
                break;
            case 'mkv':
            case '3gp':
            case 'mp4':
                return 'video';
                break;
            case 'jpg':
            case 'jpeg':
            case 'png':
                return 'image';
                break;
        }
    }

    public function send_push($id) {
        $get_notification_data = \App\NotificationsData::where("nd_id", $id)->first();
        if ($get_notification_data) {
            $push_content = $get_notification_data->nd_content;
            $push_target = $get_notification_data->nd_target;

            $ios_payload = array();
            $ios_payload['aps']['icon'] = 'appicon';
            $ios_payload['aps']['vibrate'] = 'true';
            $ios_payload['aps']['badge'] = 0;
            $ios_payload['aps']['sound'] = "default";
            $ios_payload['aps']['alert'] = (string) $push_content;
            $ios_payload['aps']['type'] = 1;

            $android_payload = array();
            $android_payload['android']['icon'] = 'appicon';
            $android_payload['android']['vibrate'] = 'true';
            $android_payload['android']['badge'] = 1;
            $android_payload['android']['sound'] = "default";
            $android_payload['android']['message'] = (string) $push_content;
            $android_payload['android']['type'] = 1;

            $users_device_tokens_data = \App\User::join("user_device_tokens", "udt_u_id", "u_id")->where("u_status", 1)->select("u_id", "udt_device_token", "udt_device_type");
            if ($push_target == 2) {
                $users_device_tokens_data = $users_device_tokens_data->where("udt_device_type", "android");
            } elseif ($push_target == 3) {
                $users_device_tokens_data = $users_device_tokens_data->where("udt_device_type", "ios");
            }
            $users_device_tokens_data = $users_device_tokens_data->get();
            //dd($users_device_tokens_data);exit;
            $android_users = array();
            $ios_users = array();
            foreach ($users_device_tokens_data as $toke_data) {
                if (!empty($toke_data->udt_device_token)) {
                    if ($toke_data->udt_device_type == "ios") {
                        $ios_users[] = $toke_data->udt_device_token;
                    } else {
                        $android_users[] = $toke_data->udt_device_token;
                    }
                }

                $notification = new \App\Notification();
                $notification->n_reciever_id = $toke_data->u_id;
                $notification->n_nd_id = $get_notification_data->nd_id;
                $notification->save();

                $android_payload['android']['id'] = $notification->n_id;
                $android_payload['android']['date'] = date("Y-m-d H:i:s");
            }
            //echo "<pre>";print_r($ios_users);exit;
            if (!empty($android_users)) {
                $this->send_notification_android($android_users, $android_payload);
            }
            if (!empty($ios_users)) {
                foreach ($ios_users as $token) {
                    $this->send_notification_ios($token, $ios_payload);
                }
            }
        }
    }

    public function charge_customer($request, $tour_owner_price, $admin_price, $user, $tour, $log_file) {
//        dd($request->all());
        $stripe_secret = Settings::where('s_name', 'stripe_secret')->pluck('s_value')->first();
        $stripe_secret = !empty($stripe_secret) ? $stripe_secret : env('STRIPE_SECRET');

        try {
            \Stripe\Stripe::setApiKey($stripe_secret);

            if (!empty($request->payment_method_id)) {

                # Create the PaymentIntent
                $create_intent = array(
                    'payment_method' => $request->payment_method_id,
                    'amount' => trim(trim($tour_owner_price + $admin_price) * 100),
                    "currency" => "USD",
                    'confirmation_method' => 'manual',
                    'confirm' => true,
                    "receipt_email" => $user->u_email,
                    "description" => "Payment of purchased " . $tour->tour_name . " tour.",
                    'setup_future_usage' => 'off_session',
                );

                if (!empty($request->u_stripe_account_id)) {
                    $create_intent["transfer_data"] = [
                        'destination' => $request->u_stripe_account_id,
                        "amount" => trim($tour_owner_price * 100)
                    ];
                }

                if (!empty($request->u_stripe_id)) {
                    $create_intent['customer'] = $request->u_stripe_id;
                }

                file_put_contents($log_file, "\n\n PaymentIntent::create = " . json_encode($create_intent) . " \n", FILE_APPEND | LOCK_EX);

                $intent = \Stripe\PaymentIntent::create($create_intent);
            } else if (!empty($request->payment_intent_id)) {
                $intent = \Stripe\PaymentIntent::retrieve($request->payment_intent_id);
                $intent->confirm();
            } else {
                return "Problem while payment, Payment is not deducted, Please try again ";
            }

            if ($intent->status == 'succeeded') {
                try {
                    if (empty($request->u_stripe_id)) {
                        $customer = \Stripe\Customer::create([
                                    'payment_method' => $intent->payment_method,
                                    "email" => $user->u_email,
                        ]);
                    } else {
                        $payment_method = \Stripe\PaymentMethod::retrieve($intent->payment_method);
                        $payment_method->attach(['customer' => $request->u_stripe_id]);
                    }
                } catch (\Exception $e) {
                    file_put_contents($log_file, "\n\n succeeded Exception : " . $e->getMessage(), FILE_APPEND | LOCK_EX);
                    //
                }

                return $intent;
            } elseif ($intent->status == 'requires_action' && $intent->next_action->type == 'use_stripe_sdk') {
                return array(
                    'requires_action' => true,
                    'payment_intent_client_secret' => $intent->client_secret
                );
            } elseif ($intent->status == 'requires_source_action' && $intent->next_action->type == 'use_stripe_sdk') {
                return array(
                    'requires_action' => true,
                    'payment_intent_client_secret' => $intent->client_secret
                );
            } else {
                return "Something else happened, invalid payment intent status";
            }
        } catch (\Stripe\Exception\CardException $e) {
            // Since it's a decline, \Stripe\Exception\CardException will be caught
//            echo 'Status is:' . $e->getHttpStatus() . '\n';
//            echo 'Type is:' . $e->getError()->type . '\n';
//            echo 'Code is:' . $e->getError()->code . '\n';
//            // param is '' in this case
//            echo 'Param is:' . $e->getError()->param . '\n';
//            echo 'Message is:' . $e->getError()->message . '\n';
            file_put_contents($log_file, "\n\n CardException : " . $e->getError()->message, FILE_APPEND | LOCK_EX);
            return "Stripe: " . $e->getError()->message;
        } catch (\Stripe\Exception\RateLimitException $e) {
            // Too many requests made to the API too quickly
            file_put_contents($log_file, "\n\n RateLimitException : " . $e->getMessage(), FILE_APPEND | LOCK_EX);
            return "Stripe: " . $e->getMessage();
        } catch (\Stripe\Exception\InvalidRequestException $e) {
            // Invalid parameters were supplied to Stripe's API
            file_put_contents($log_file, "\n\n InvalidRequestException : " . $e->getMessage(), FILE_APPEND | LOCK_EX);
            return "Stripe: " . $e->getMessage();
        } catch (\Stripe\Exception\AuthenticationException $e) {
            // Authentication with Stripe's API failed
            // (maybe you changed API keys recently)
            file_put_contents($log_file, "\n\n AuthenticationException : " . $e->getMessage(), FILE_APPEND | LOCK_EX);
            return "Stripe: " . $e->getMessage();
        } catch (\Stripe\Exception\ApiConnectionException $e) {
            // Network communication with Stripe failed
            file_put_contents($log_file, "\n\n ApiConnectionException : " . $e->getMessage(), FILE_APPEND | LOCK_EX);
            return "Stripe: " . $e->getMessage();
        } catch (\Stripe\Exception\ApiErrorException $e) {
            // Display a very generic error to the user, and maybe send
            // yourself an email
            file_put_contents($log_file, "\n\n ApiErrorException : " . $e->getMessage(), FILE_APPEND | LOCK_EX);
            return "Stripe: " . $e->getMessage();
        } catch (\Exception $e) {
            file_put_contents($log_file, "\n\n Exception : " . $e->getMessage(), FILE_APPEND | LOCK_EX);
            return "Stripe: " . $e->getMessage();
        }
    }

    public function fetch_stripe_account($id) {
        try {
            $stripe_secret = Settings::where('s_name', 'stripe_secret')->pluck('s_value')->first();
            $stripe_secret = !empty($stripe_secret) ? $stripe_secret : env('STRIPE_SECRET');

            \Stripe\Stripe::setApiKey($stripe_secret);
            \Stripe\Stripe::setApiVersion("2019-12-03");

            $user = User::where('u_id', $id)->where('u_status', 1)->first();

            if (empty($user) || empty($user->u_stripe_account_id)) {
                return 2;
            }

            $stripe_account = \Stripe\Account::retrieve($user->u_stripe_account_id);

            if (empty($stripe_account)) {
                return 2;
            } else if ($stripe_account->charges_enabled == false) {
                return 2;
            } else if ($stripe_account->capabilities->card_payments == "inactive") {
                return 2;
            } else if ($stripe_account->capabilities->transfers == "inactive") {
                return 2;
            } else if ($stripe_account->details_submitted == false) {
                return 2;
            } else if ($stripe_account->individual->verification->status == "unverified") {
                return 2;
            } else if (!$stripe_account->individual->id_number_provided) {
                return 2;
            } else if (!$stripe_account->individual->ssn_last_4_provided) {
                return 2;
            }
            return 1;
        } catch (\Stripe\Exception\InvalidRequestException $e) {
            return 2;
        } catch (\Stripe\Exception\AuthenticationException $e) {
            return 2;
        } catch (\Stripe\Exception\ApiConnectionException $e) {
            return 2;
        } catch (\Stripe\Exception\ApiErrorException $e) {
            return 2;
        } catch (\Exception $e) {
            return 2;
        }
        return 2;
    }

}
