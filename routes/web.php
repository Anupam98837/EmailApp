<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\API\TrackingController;
use App\Http\Controllers\UnsubscribeController;
use App\Http\Controllers\ThemeCssController;

// Route::get('/', function () {
//     return view('pages/common/home');
// });


Route::get('/info', function () {
    // capture phpinfo() output and return as an HTML response
    ob_start();
    phpinfo();
    $info = ob_get_clean();
    return response($info)->header('Content-Type', 'text/html');
});

//USERS
Route::get('/', function () {
    return view('pages/users/user/pages/common/loginSignup');
});
Route::get('/user/dashboard', function () {
    return view('pages/users/user/pages/common/dashboard');
});
Route::get('/user/list/manage', function () {
    return view('pages/users/user/pages/manageUserList/manageList');
});
Route::get('/user/template/manage', function () {
    return view('pages/users/user/pages/manageTemplate/manageTemplate');
});
Route::get('/user/campaign/create', function () {
    return view('pages/users/user/pages/manageCampaign/createCampaign');
});
Route::get('/user/campaign/view', function () {
    return view('pages/users/user/pages/manageCampaign/viewCampaign');
});
Route::get('/campaigns/{id}/report', function($id){
    return view('modules.report.campaignReport');
});
Route::get('/campaigns/report/all', function () {
    return view('pages/users/user/pages/report/allCampaignReport');
});
Route::get('/mailer/manage', function () {
    return view('pages/users/user/pages/settings/managMailer');
});
Route::get('/user/profile/manage', function () {
    return view('pages/users/user/pages/settings/manageProfile');
});
Route::get('/media', function () {
    return view('pages/users/user/pages/settings/manageMedia');
});
Route::get('/editor', function () {
    return view('modules.template.editor');
});
// Route::get('/theme', function () {
//     return view('pages/users/user/pages/theme/manageTheme');
// });
Route::get('/plans', function () {
    return view('pages/users/user/pages/plans/myplan');
});

//Email Open Tracker
// Route::prefix('track')->group(function(){
//     Route::get('/open/{campaign_uuid}/{subscriber_id}', [TrackingController::class, 'open'])
//      ->name('track.open');
//      Route::get('click/{campaign_uuid}/{subscriber_id}', [TrackingController::class, 'click'])
//          ->name('track.click');
// });

// routes/web.php

// use App\Http\Controllers\API\TrackingController;
//ADMIN
Route::get('/admin/login', function () {
    return view('pages/users/admin/pages/common/login');
});
Route::get('/admin/dashboard', function () {
    return view('pages/users/admin/pages/common/dashboard');
});
Route::get('/admin/users/manage', function () {
    return view('pages/users/admin/pages/users/manageUser');
});
Route::get('/admin/mailer/manage', function () {
    return view('pages/users/admin/pages/mailer/manageMailer');
});
Route::get('/admin/subscription/manage', function () {
    return view('pages/users/admin/pages/subscriptionPlan/manageSubscriptionPlan');
});
Route::get('/admin/theme/manage', function () {
    return view('pages/users/admin/pages/theme/manageTheme');
});
Route::get('/admin/payment-gateway/manage', function () {
    return view('pages/users/admin/pages/paymentGateway/managePaymentGateway');
});

Route::prefix('track')->group(function(){
    Route::get('open/{campaign_uuid}/{subscriber_id}', [TrackingController::class, 'open'])
         ->name('track.open');
    Route::get('click/{campaign_uuid}/{subscriber_id}', [TrackingController::class, 'click'])
         ->name('track.click');
});
Route::get('track/unsubscribe/{campaign_uuid}/{subscriber_id}', [TrackingController::class, 'unsubscribe'])
    ->name('track.unsubscribe');

// routes/web.php


Route::get('/assets/css/theme/user.css', [ThemeCssController::class, 'userCss'])
    ->name('theme.user.css');

Route::get('/assets/css/theme/preview/{theme}.css', [ThemeCssController::class, 'preview'])
    ->whereNumber('theme')
    ->name('theme.preview.css');
