<?php

namespace App\Traits;

use DateTime;

trait ConversationIdGenerator
{

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
}
