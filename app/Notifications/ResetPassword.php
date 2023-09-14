<?php

namespace App\Notifications;

use Illuminate\Notifications\Notification;
use Illuminate\Notifications\Messages\MailMessage;

class ResetPassword extends Notification
{
    public $token;

    public function __construct($token)
    {
        $this->token = $token;
    }

    public function via($notifiable)
    {
        return ['mail'];
    }
    
      /**
     * Build the mail representation of the notification.
     *
     * @param  mixed  $notifiable
     * @return \Illuminate\Notifications\Messages\MailMessage
     */
    public function toMail($notifiable) {
        return (new MailMessage)
                        ->greeting('Hello '.env('APP_NAME').' member,')
                        ->subject('Your new password for '.env('APP_NAME').' application')
                        ->line('Please select a new password using the following link:')
                        ->action('Reset Password', url('api/password/reset?email=' . $notifiable->u_email, $this->token))
                        ->line('If you are unable to open the link by clicking on it, paste the following Internet address into your Internet browser to change your password that way:')
//                        ->action(url('api/password/reset', $this->token),url('api/password/reset', $this->token))
                        ->line('We hope you enjoy using '.env('APP_NAME').'!');
//                        ->line(env('APP_NAME').' Team')
//                        ->action('contact@tap-pet.com','contact@tap-pet.com');
//                        ->action('http://www.tap-pet.com/','http://www.tap-pet.com/');
                        
    }
}