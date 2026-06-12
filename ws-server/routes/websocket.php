<?php


use Illuminate\Support\Facades\Log;
use Illuminate\Http\Request;
use SwooleTW\Http\Websocket\Facades\Websocket;



//Route::get('/test',["uses"=>"WebsocketController@getTest"]);


//Websocket::on('connect', function ($websocket, $request) {
//    // in connect callback, illuminate request will be injected here
//    $websocket->emit('message', 'welcome');
//});
//
//Websocket::on('message', function ($websocket, $request) {
//    // in connect callback, illuminate request will be injected here
//
//    $websocket->emit('message', 'welcome');
//});
//
//Websocket::on('disconnect', function ($websocket) {
//    // this callback will be triggered when a websocket is disconnected
//
//    Log::info("链接断开：".json_encode($websocket));
//});

