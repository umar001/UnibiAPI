<?php

use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

Auth::routes();

Route::group(['middleware' => 'auth'], function () {
    Route::get('/posts','Admin\PostController@index')->name('getposts');
    Route::get('/reported-posts','Admin\PostController@getreportedposts')->name('reported-posts');
    Route::get('/deletepost/{id}','Admin\PostController@deletepost')->name('deletepost');
    Route::post('/deletepost','Admin\PostController@deletepost')->name('deletepost');
    Route::resource('users','Admin\UserController');
    Route::post('/deleteuser','Admin\UserController@deleteuser')->name('deleteuser');
    // Route::get('/deleteUser/{id}')
    //suggestion list
    Route::get('/suggestion', 'Admin\SuggestionController@index')->name('suggestion');
    Route::get('/suggestion-status/{id}/{status}', 'Admin\SuggestionController@changestatue')->name('suggestion-status');
    Route::get('/suggestion-delete/{id}', 'Admin\SuggestionController@delete')->name('suggestion-delete');
    Route::post('/save-suggestion', 'Admin\SuggestionController@save')->name('save-suggestion');
    Route::post('/getreportreasonlist', 'Admin\PostController@rlist')->name('getreportreasonlist');
    //end
    //deactive account suggestion 
    Route::get('/deactive-suggestion', 'Admin\DeactiveAccountController@index')->name('deactive-suggestion');
    Route::get('/deactive-suggestion-status/{id}/{status}', 'Admin\DeactiveAccountController@changestatue')->name('deactive-suggestion-status');
    Route::get('/deactive-suggestion-delete/{id}', 'Admin\DeactiveAccountController@delete')->name('deactive-suggestion-delete');
    Route::post('/deactive-save-suggestion', 'Admin\DeactiveAccountController@save')->name('deactive-save-suggestion');
    Route::get('/deactive-feedback', 'Admin\DeactiveAccountController@feedback')->name('deactive-feedback');
    //end of deactive account suggestion
    //intersets
    Route::get('/intersets', 'Admin\IntersetController@index')->name('intersets');
    Route::post('/save-options', 'Admin\IntersetController@create')->name('save-options');
    Route::get('/delete-options/{table}/{key}', 'Admin\IntersetController@delete')->name('delete-options');
    //end of intersets
    //abuses character
    Route::get('/Abuses', 'Admin\IntersetController@getAbuses')->name('Abuses');
    Route::post('/save-Abuses', 'Admin\IntersetController@saveAbuses')->name('save-Abuses');
    //end of abuses
    Route::get('/', 'HomeController@index');
    Route::get('/home', 'HomeController@index');
    Route::get('/notification', 'HomeController@notification');
    // Route::get('/role', 'HomeController@role');
    // Route::get('/firebase', 'HomeController@firebase');
    // Route::get('/setting', 'HomeController@setting')->name('setting');
    // Setting Page
    Route::prefix('setting')->group( function(){
        Route::get('/', 'SettingController@index')->name('setting');
        Route::post('/user-data', 'SettingController@getUserData');
        Route::post('/update-roles', 'SettingController@updateUserRole');
        Route::post('/user-delete', 'SettingController@userDelete');
        
    });
});

