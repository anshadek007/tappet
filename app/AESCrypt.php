<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class AESCrypt extends Model {

    private $iv;
    private $key;
    private $bit; //Only can use 128, 256
    private $encryptkey;

    function __construct($key, $bit = 256, $iv = "") {

        // gen key
        /* if($bit == 256){
          $this->key = hash('SHA256', $key, true);
          }else{
          $this->key = hash('MD5', $key, true);
          } */

        $this->key = $key;

        // gen iv
        if ($iv != "") {
            $this->iv = $iv;
        } else {
            $this->iv = chr(0) . chr(0) . chr(0) . chr(0) . chr(0) . chr(0) . chr(0) . chr(0) . chr(0) . chr(0) . chr(0) . chr(0) . chr(0) . chr(0) . chr(0) . chr(0); //IV is not set. It doesn't recommend.
        }
    }

    function encrypt($str) {
        return $this->opensslEncrypt($str);
    }

    function decrypt($str) {
        return $this->opensslDecrypt($str);
    }

    private function opensslEncrypt($str) {

        if (empty($str)) {
            return $str;
        }

        $data = openssl_encrypt($str, 'AES-256-CBC', $this->key, OPENSSL_RAW_DATA, $this->iv);
        return base64_encode($data);
    }

    private function opensslDecrypt($str) {

        if (empty($str)) {
            return $str;
        }

        $decrypted = openssl_decrypt(base64_decode($str), 'AES-256-CBC', $this->key, OPENSSL_RAW_DATA, $this->iv);
        return $decrypted;
    }

    static function encryptString($content) {
        return $content;
        if (empty($content)) {
            return "";
        }

        $encrypt_key = config('constants.ENCRYPT_KEY');
        $encrypt_iv = config('constants.ENCRYPT_IV');
        $aes = new AESCrypt($encrypt_key, 256, $encrypt_iv);
        return $aes->encrypt($content);
    }

    static function decryptString($content) {
        return $content;
        if (empty($content)) {
            return "";
        }

        $content = str_replace("%2B", "+", $content);
        $content = str_replace(" ", "+", $content);
        $encrypt_key = config('constants.ENCRYPT_KEY');
        $encrypt_iv = config('constants.ENCRYPT_IV');
        $aes = new AESCrypt($encrypt_key, 256, $encrypt_iv);
        return $aes->decrypt($content);
    }

}
