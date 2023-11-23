<?php

namespace App\Traits;

use ZEGO\ZegoServerAssistant;
use ZEGO\ZegoErrorCodes;
use DateTime;
require_once 'auto_loader.php';
class ConversationIdGenerator
{

   
 // Permission bit definition
 const PrivilegeKeyLogin   = 1; // Permission for login
 const PrivilegeKeyPublish = 2; // Permission for push stream
 
 // Permission switch definition
 const PrivilegeEnable     = 1; // Enable
 const PrivilegeDisable    = 0; // Disable
 

    public function conversation_id_generator (){
   

        // Create a DateTime object for the current date and time
        $dateTime = new DateTime();

        // Format the date and time as a string
        $dateString = $dateTime->format('YmdHis');

        // Generate a unique identifier
        $uniqueId = str_replace('.', '', uniqid($dateString, true));

        // Output the unique ID
        return $uniqueId;
    }


public function zego_key($serverSecret,$appId,$user_id,$remainTimeInSecond){

    

        //
// Authorization token generation example, contact ZEGO technical support to enable this feature before use
//



// Please modify appID to your own appId, appid is a number
// Example: 1234567890
$appId = $appId;

// Please modify serverSecret to your own serverSecret, serverSecret is a string
// Example: 'fa94dd0f974cf2e293728a526b028271'
$serverSecret = $serverSecret;

// Please modify userId to the user's userId
$userId = $user_id;

$roomId = "room1";

$rtcRoomPayLoad = [
    'room_id' => $roomId, // Room ID; used to strongly verify the room ID of the interface
    'privilege' => [     // List of permission bit switches; used to strongly verify the operation permission of the interface
        'PrivilegeKeyLogin' => 1, // Indicates that login is allowed
        'PrivilegeKeyPublish' => 2,// Indicates that publishing is not allowed
    ],
    'stream_id_list' => [] // List of streams; used to strongly verify the stream ID of the interface; can be empty, if it is empty, the stream ID verification is not performed
];

$payload = json_encode($rtcRoomPayLoad);


// 3600 is the token expiration time, in seconds
$token = ZegoServerAssistant::generateToken04($appId, $userId, $serverSecret, $remainTimeInSecond, $payload);
if( $token->code == ZegoErrorCodes::success ){
  #...
}
return $token->token;

//demo
//3AAAAAGCKKT8AEGZvcmtpc2xieW4wdTI4cXcAoPBuvYE1pAu6k+I9aVF4ooQFkG60sNBVZd8quE2Y/lIgkr60HZT5nP1fUgYABO+wpdT7EOJi00k1oycbtpP3E4wsOgAU11gyPSkBVyJ3V4i2nma8v9IPuH5r9WOVSqsngwWDAlBVxjVO14cWyfGc3UDynsALk+qd9Rk8PVrhWTNWpqZxCsUDyk79omSC4wI4CY/wLmiM+AN+wcL9ohGUNbo=

    }
}
