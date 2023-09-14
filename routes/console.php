<?php

use Illuminate\Foundation\Inspiring;

/*
  |--------------------------------------------------------------------------
  | Console Routes
  |--------------------------------------------------------------------------
  |
  | This file is where you may define all of your Closure based console
  | commands. Each Closure is bound to a command instance allowing a
  | simple approach to interacting with each command's IO methods.
  |
 */


Artisan::command('send_friend_invite_push {n_id}', function ($n_id) {
    $send_push = new App\Http\Controllers\CommonController();
    $send_push->send_friend_invite_push($n_id);
})->describe('Send friend invitation push');

Artisan::command('send_friend_invite_accept_push {n_id}', function ($n_id) {
    $send_push = new App\Http\Controllers\CommonController();
    $send_push->send_friend_invite_accept_push($n_id);
})->describe('Send friend invitation accept push');

Artisan::command('send_friend_invite_reject_push {n_id}', function ($n_id) {
    $send_push = new App\Http\Controllers\CommonController();
    $send_push->send_friend_invite_reject_push($n_id);
})->describe('Send friend invitation reject push');

Artisan::command('send_added_into_group_push', function () {
    $send_push = new App\Http\Controllers\CommonController();
    $send_push->send_added_into_group_push();
})->describe('Send added to a group push');

Artisan::command('send_added_into_event_push', function () {
    $send_push = new App\Http\Controllers\CommonController();
    $send_push->send_added_into_event_push();
})->describe('Send added to a event push');

Artisan::command('send_post_like_push {n_id}', function ($n_id) {
    $send_push = new App\Http\Controllers\CommonController();
    $send_push->send_post_like_push($n_id);
})->describe('Send post like push');

Artisan::command('send_post_comment_push {n_id}', function ($n_id) {
    $send_push = new App\Http\Controllers\CommonController();
    $send_push->send_post_comment_push($n_id);
})->describe('Send post comment push');

Artisan::command('test_ios_push {token}', function ($token) {
    $send_push = new App\Http\Controllers\CommonController();
    $send_push->test_ios_push($token);
})->describe('Send test push');

Artisan::command('test_android_push {token}', function ($token) {
    $send_push = new App\Http\Controllers\CommonController();
    $send_push->test_android_push($token);
})->describe('Send test push');

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->describe('Display an inspiring quote');

Artisan::command('sendpush {nd_id}', function ($nd_id) {
    $send_push = new App\Http\Controllers\CommonController();
    $send_push->send_push($nd_id);
})->describe('Send global push notification');

Artisan::command('send_pending_push {date}', function ($date) {
    $send_pending_push = new App\Http\Controllers\CommonController();
    $send_pending_push->send_pending_push($date);
})->describe('Send pending push notification by date');

Artisan::command('send_tour_invite_push {n_id} {tour_id}', function ($n_id, $tour_id) {
    $send_push = new App\Http\Controllers\CommonController();
    $send_push->send_tour_invite_push($n_id, $tour_id);
})->describe('Send tour invitation push');
