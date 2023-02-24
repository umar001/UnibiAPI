<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

Route::prefix('v1')->group( function(){
    Route::post('register','Auth\PassportAuthController@register');
    Route::post('login', 'Auth\PassportAuthController@login');
    Route::post('password','Auth\PassportAuthController@password');
    Route::post('passwordReset','Auth\PassportAuthController@resetPassword');
    Route::middleware('auth:app-api')->group(function () {
        // Interests
        Route::resource('interest', 'InterestsController');
        // Topics
        Route::resource('topics', 'TopicsController');
        // language
        Route::resource('language', 'LanguageController');
        
        // Preferences
        Route::resource('preference', 'PreferenceController');
        // Profile Controller
        Route::resource('userProfile', 'UserProfileController');
        Route::post('deleteAccount', 'UserProfileController@deleteAccount');
        Route::get('userFriendList', 'UserProfileController@userFriendList');
        Route::get('getUserProfile', 'UserProfileController@getUserProfile');
        Route::post('insertProfileImages', 'UserProfileController@profileImages');
        Route::post('multipleImageUpload', 'UserProfileController@multipleImageUpload');
        Route::post('updateUserProfileImage', 'UserProfileController@updateProfileImages');
        Route::post('deleteUserImage', 'UserProfileController@deleteUserImage');
        Route::get('userProfileImages', 'UserProfileController@userProfileImagesList');
        Route::post('userLocation', 'UserProfileController@userLocation');
        Route::post('userPreferenceAgeRange', 'UserProfileController@userPreferenceAgeRange');
        Route::get('userAgeRange', 'UserProfileController@getUserAgeRange');
        Route::post('verifyEmail','UserProfileController@verifyEmail');
        Route::post('updateUserPassword','UserProfileController@updatePassword');
       
        Route::post('emailCodeVerification','UserProfileController@emailCodeVerification');
        Route::get('getPostNearbyPerson','UserProfileController@postLocationFinder');
        Route::get('nearbyUser','UserProfileController@userLocationFinder');
        Route::get('postNearToYourLocation','UserProfileController@postNearToYourLocation');
        Route::post('userDetail','UserProfileController@userDetails');
        // Post Controller
        Route::resource('userPost', 'UserPostController');
        Route::post('UpdatePost', 'UserPostController@updatePostdata');
        Route::post('sendPresentRequest', 'UserPostController@sendPresentRequest');
        Route::post('getPostRequestList', 'UserPostController@getPostRequestList');
        Route::post('acceptPresentRequest', 'UserPostController@acceptPresentRequest');
        Route::post('declinedPresentRequest', 'UserPostController@declinedPresentRequest');
        Route::post('cancelPresentRequest', 'UserPostController@cancelPresentRequest');
        
        // Friend Request Controller
        Route::resource('friendRequest','UserFriendsRequestController');
        Route::post('acceptFriendRequest','UserFriendsRequestController@acceptFriendRequest');
        Route::get('sentFriendRequestList','UserFriendsRequestController@friendRequestList');
        Route::get('receiveFriendReequestList','UserFriendsRequestController@receiveFriendRequestList');
        // Auth Controller
        Route::post('logout', 'Auth\PassportAuthController@logout');
        Route::get('/email/verify', function () {
            return view('auth.verify-email');
        });
        // Notifications
        Route::post('notification','NotificationsController@index');
        Route::post('notificationToAllUsers','NotificationsController@multiUserNotification');
        // Route::get('sendNotification')
        //for api to get report suggestion
        Route::get('getreportsuggestion', 'PostController@getsuggestionlist');
        Route::get('getDeactiveSuggestion', 'UserProfileController@getsuggestionlist');
        Route::post('reportpost', 'PostController@reportpost');
        //end of report suggestion
    });
});
