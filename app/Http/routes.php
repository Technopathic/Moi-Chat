<?php

/*
|--------------------------------------------------------------------------
| Application Routes
|--------------------------------------------------------------------------
|
| Here is where you can register all of the routes for an application.
| It's a breeze. Simply tell Laravel the URIs it should respond to
| and give it the controller to call when that URI is requested.
|
*/

Route::group(['prefix' => 'api'], function()
{

  Route::resource('authenticate', 'AuthenticateController', ['only' => ['index']]);
  Route::post('authenticate', 'AuthenticateController@authenticate');
  Route::get('authenticate/user', 'AuthenticateController@getAuthenticatedUser');
  Route::post('signUp', 'AuthenticateController@doSignUp');
  Route::get('refreshToken', 'AuthenticateController@refreshToken');

  Route::get('getChannels/{lat}&{lon}', 'ChannelsController@getChannels');
  Route::get('createChannel', 'ChannelsController@createChannel');
  Route::post('storeChannel', 'ChannelsController@storeChannel');
  Route::post('chanAuth', 'ChannelsController@chanAuth');
  Route::get('getAuths', 'ChannelsController@getAuths');
  Route::get('refreshChannels', 'ChannelsController@refreshChannels');

  Route::get('getMessages/{id}', 'MessagesController@getMessages');
  Route::post('storeMessage', 'MessagesController@storeMessage');
  Route::get('showChannel/{id}', 'MessagesController@showChannel');

  Route::post('storeAvatar', 'ChannelsController@storeAvatar');

  Route::get('getFriends', 'ChannelsController@getFriends');
  Route::get('storeFriend/{id}', 'ChannelsController@storeFriend');
  Route::get('getFmessages/{id}', 'MessagesController@getFmessages');
  Route::post('storeFmessage', 'MessagesController@storeFmessage');

  Route::get('storeBan/{id}/{chanID}', 'ChannelsController@storeBan');

  Route::get('getBlocks', 'ChannelsController@getBlocks');
  Route::get('storeBlock/{id}', 'ChannelsController@storeBlock');
});

Route::any('{path?}', 'ChannelsController@index')->where("path", ".+");
