<?php

use Illuminate\Http\Request;

Route::get('/test',["uses"=>"testController@getTest"]);
//Route::get('/test',["uses"=>"testController@getTest"])->middleware('testmiddleware');

