<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\API\UserController;
use App\Http\Controllers\API\ListManagementController;
use App\Http\Controllers\API\TemplateController;
use App\Http\Controllers\API\TemplateDraftController;
use App\Http\Controllers\API\MediaController;
use App\Http\Controllers\API\CampaignController;
use App\Http\Controllers\API\TrackingController;
use App\Http\Controllers\API\ReportController;
use App\Http\Controllers\API\MailerController;
use App\Http\Controllers\API\DashboardController;
use App\Http\Controllers\API\TestMailController;
use App\Http\Controllers\API\ThemeController;
use App\Http\Controllers\API\AdminController;
use App\Http\Controllers\API\AdminMailerController;
use App\Http\Controllers\API\SubscriptionPlanController;
use App\Http\Controllers\API\PaymentController;
use App\Http\Controllers\API\ThemeSettingsController;
use App\Http\Controllers\API\ThemeCssController;


Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');
Route::get('/dashboard', [DashboardController::class, 'index']);

/*
|--------------------------------------------------------------------------
| Admin Routes
|--------------------------------------------------------------------------
*/
Route::prefix('admin')->group(function () {
    Route::post('login', [AdminController::class, 'login']);

    Route::middleware('checkRole:admin')->group(function () {
        Route::post('logout', [AdminController::class, 'logout']);
        Route::get('dashboard', [DashboardController::class, 'adminDashboard']);
        Route::get('profile', [AdminController::class, 'profile']);
        Route::get('users', [UserController::class, 'adminGetUsers']);
        Route::get('users/{id}', [UserController::class, 'adminGetUserDetail']);

        // New admin user management routes
        Route::post('users/register', [UserController::class, 'adminRegisterUser']);
        Route::put('users/{id}', [UserController::class, 'adminUpdateUser']);
        Route::patch('users/{id}/toggle-status', [UserController::class, 'toggleUserStatus']);
    });
});
/*
|--------------------------------------------------------------------------
| Public User Routes
|--------------------------------------------------------------------------
*/
Route::post('/register', [UserController::class, 'register']);
Route::post('/login',    [UserController::class, 'login']);

/*
|--------------------------------------------------------------------------
| Protected User Routes (token in Authorization: Bearer {token})
|--------------------------------------------------------------------------
*/
Route::post('/logout',         [UserController::class, 'logout']);
Route::get( '/profile',         [UserController::class, 'profile']);
Route::put( '/profile',         [UserController::class, 'updateProfile']);
Route::put( '/password',        [UserController::class, 'updatePassword']);

/*
|--------------------------------------------------------------------------
| List And USers Creation 
|--------------------------------------------------------------------------
*/
Route::prefix('lists')->group(function () {
    // Lists
    Route::get('/',                        [ListManagementController::class, 'index']);       // GET    /api/lists
    Route::post('/',                       [ListManagementController::class, 'store']);       // POST   /api/lists
    Route::get('{id}',                     [ListManagementController::class, 'show']);        // GET    /api/lists/{id}
    Route::put('{id}',                     [ListManagementController::class, 'update']);      // PUT    /api/lists/{id}
    Route::patch('{id}/toggle',            [ListManagementController::class, 'toggle']);      // PATCH  /api/lists/{id}/toggle
    Route::delete('{id}',                  [ListManagementController::class, 'destroy']);     // DELETE /api/lists/{id}
    Route::delete('{id}/users/empty', [ListManagementController::class, 'empty']); // purge all subscribers
    // Subscribers (list_users)
    Route::post('{id}/users',              [ListManagementController::class, 'addUser']);     // POST   /api/lists/{id}/users
    Route::post('{id}/users/import', [ListManagementController::class, 'importUsers']);       // POST   /api/lists/{id}/users/import
    Route::get('{id}/users',               [ListManagementController::class, 'viewUsers']);   // GET    /api/lists/{id}/users
    Route::put('{id}/users/{user_uuid}',   [ListManagementController::class, 'editUser']);    // PUT    /api/lists/{id}/users/{user_uuid}
    Route::delete('{id}/users/{user_uuid}',[ListManagementController::class, 'deleteUser']);  // DELETE /api/lists/{id}/users/{user_uuid}
    Route::patch('{id}/users/{user_uuid}/toggle', [ListManagementController::class, 'toggleUser']); // PATCH /api/lists/{id}/users/{user_uuid}/toggle
});
/*
|--------------------------------------------------------------------------
| Template Manage
|--------------------------------------------------------------------------
*/
Route::prefix('templates')
     ->group(function () {
         Route::get('/',              [TemplateController::class, 'index']);
         Route::post('/',             [TemplateController::class, 'store']);
         Route::get('{uuid}',         [TemplateController::class, 'show']);
         Route::put('{uuid}',         [TemplateController::class, 'update']);
         Route::delete('{uuid}',      [TemplateController::class, 'destroy']);
         Route::patch('{uuid}/toggle',[TemplateController::class, 'toggle']);
         Route::get('{uuid}/preview', [TemplateController::class, 'preview']);
         Route::post('{uuid}/images', [TemplateController::class, 'uploadImage']);
         Route::get('{uuid}/images',  [TemplateController::class, 'listImages']);
     });
/*
|--------------------------------------------------------------------------
| Template Draft Manage
|--------------------------------------------------------------------------
*/
Route::prefix('template-drafts')->group(function(){
    Route::post('/',        [TemplateDraftController::class, 'store']);
    Route::get('/',         [TemplateDraftController::class, 'index']);
    Route::get('{uuid}',    [TemplateDraftController::class, 'preview']);
    Route::patch('{uuid}',  [TemplateDraftController::class, 'update']);
    Route::delete('{uuid}', [TemplateDraftController::class, 'destroy']);
    Route::post('{uuid}/copy',    [TemplateDraftController::class, 'copy']);
    Route::post('{uuid}/approve', [TemplateDraftController::class, 'approve']);
});     
/*
|--------------------------------------------------------------------------
| Media Manage
|--------------------------------------------------------------------------
*/
Route::prefix('media')->group(function(){
        Route::get('/',          [MediaController::class, 'index']);
        Route::post('/',         [MediaController::class, 'store']);
        Route::delete('{id}',    [MediaController::class, 'destroy']);
    });
/*
|--------------------------------------------------------------------------
| campaign Manage
|--------------------------------------------------------------------------
*/     
Route::prefix('campaign')->group(function () {
        Route::get('/',            [CampaignController::class, 'index']);
        Route::post('/',           [CampaignController::class, 'store']);
        Route::get('{campaign}',   [CampaignController::class, 'show']);
        Route::put('{campaign}',   [CampaignController::class, 'update']);
        Route::patch('{campaign}', [CampaignController::class, 'update']);
        Route::delete('{campaign}',[CampaignController::class, 'destroy']);
        
        // Route::get('{campaign}/report', [CampaignController::class, 'report']);
            // New campaign management routes
        Route::patch('{campaign}/pause', [CampaignController::class, 'pause']);
        Route::patch('{campaign}/resume', [CampaignController::class, 'resume']);
        Route::patch('{campaign}/stop', [CampaignController::class, 'stop']);
        Route::patch('{campaign}/restart', [CampaignController::class, 'restart']);
        Route::patch('{campaign}/redo', [CampaignController::class, 'redo']);

    });
/*
|--------------------------------------------------------------------------
| Tracking Manage
|--------------------------------------------------------------------------
*/      
// Route::prefix('track')->group(function(){
//     Route::get('/open/{campaign_uuid}/{subscriber_id}', [TrackingController::class, 'open'])
//      ->name('track.open');
//     Route::get('click/{campaign_uuid}/{subscriber_id}', [TrackingController::class, 'click'])
//          ->name('track.click');
// });
/*
|--------------------------------------------------------------------------
| Report Manage
|--------------------------------------------------------------------------
*/
Route::prefix('reports')->group(function(){
    Route::get('{campaign}/overview',  [ReportController::class, 'overview']);
    Route::get('{campaign}/detailed',  [ReportController::class, 'detailed']);
});
/*
|--------------------------------------------------------------------------
| Mailer Manage
|--------------------------------------------------------------------------
*/
//ADmin
Route::prefix('admin/mailer')->middleware('checkRole:admin')->group(function () {
    Route::get('/',              [AdminMailerController::class, 'index']);
    Route::post('/',             [AdminMailerController::class, 'store']);
    Route::get('{id}',           [AdminMailerController::class, 'show']);
    Route::put('{id}',           [AdminMailerController::class, 'update']);
    Route::delete('{id}',        [AdminMailerController::class, 'destroy']);
    Route::put('{id}/status',    [AdminMailerController::class, 'toggleStatus']);
});


Route::prefix('mailer')->group(function () {
    Route::get('/',               [MailerController::class, 'index']);
    Route::post('/',              [MailerController::class, 'store']);
    Route::get('{id}',            [MailerController::class, 'show'])->whereNumber('id');
    Route::put('{id}',            [MailerController::class, 'update'])->whereNumber('id');
    Route::put('{id}/default',    [MailerController::class, 'setDefault'])->whereNumber('id');
    Route::post('clear-defaults', [MailerController::class, 'clearDefaults']);
    Route::delete('{id}',         [MailerController::class, 'destroy'])->whereNumber('id');
    Route::post('test',           [TestMailController::class, 'send']);
});
// USer
Route::prefix('theme')->group(function () {
    Route::get('/', [ThemeController::class, 'getTheme']);
    Route::post('/', [ThemeController::class, 'selectTheme']);
    Route::delete('/', [ThemeController::class, 'destroy']);
});

//Plans 

Route::prefix('plans')->group(function () {
    Route::get('my', [SubscriptionPlanController::class, 'myPlan']); // must come first
    Route::get('/', [SubscriptionPlanController::class, 'index']);
    Route::post('/', [SubscriptionPlanController::class, 'store'])->middleware('checkRole:admin');
    Route::get('{id}', [SubscriptionPlanController::class, 'show']);
    Route::put('{id}', [SubscriptionPlanController::class, 'update'])->middleware('checkRole:admin');
    Route::put('{id}/status', [SubscriptionPlanController::class, 'toggleStatus'])->middleware('checkRole:admin');
    Route::put('{id}/mailers', [SubscriptionPlanController::class, 'changeMailers'])->middleware('checkRole:admin');
    Route::post('{id}/assign', [SubscriptionPlanController::class, 'assign'])->middleware('checkRole:admin');
    Route::post('{id}/renew', [SubscriptionPlanController::class, 'renewPlan'])->middleware('checkRole:admin');
    Route::post('{id}/upgrade', [SubscriptionPlanController::class, 'upgradePlan'])->middleware('checkRole:admin');
});

//Payment

Route::post('/payments/create-order', [PaymentController::class, 'createOrder']);
Route::post('/payments/razorpay-webhook', [PaymentController::class, 'webhook']);
Route::get('/payments/{id}', [PaymentController::class, 'showPayment']);
Route::get('/subscriptions/current', [PaymentController::class, 'currentSubscription']);
Route::post('/payments/verify', [PaymentController::class, 'verifyPayment']);
Route::get('payment-subscription-history', [PaymentController::class, 'paymentSubscriptionHistory']);



// Theme settings
// In routes/api.php
Route::prefix('themes')->group(function () {
    Route::get('/',           [ThemeSettingsController::class, 'themesIndex']);
    Route::post('/',          [ThemeSettingsController::class, 'themesStore']);
    Route::get('{id}',        [ThemeSettingsController::class, 'themesShow']);
    Route::put('{id}',        [ThemeSettingsController::class, 'themesUpdate']);
    Route::delete('{id}',     [ThemeSettingsController::class, 'themesDestroy']);

    Route::post('upload',     [ThemeSettingsController::class, 'uploadAsset']);
    Route::get('logos',       [ThemeSettingsController::class, 'logosList']); // 👈 list all logos
});

Route::prefix('users/{userId}/theme')->group(function () {
    Route::get('/',    [ThemeSettingsController::class, 'userThemeShow']);
    Route::post('/',   [ThemeSettingsController::class, 'userThemeAssign']);
    Route::put('/',    [ThemeSettingsController::class, 'userThemeUpdate']);
    Route::delete('/', [ThemeSettingsController::class, 'userThemeDelete']);
});

Route::get('/my-theme', [ThemeSettingsController::class, 'myTheme']);
