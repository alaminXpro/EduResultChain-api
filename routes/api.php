<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Http\Request;

Route::get('/', function () {
    return response()->json(['message' => 'Hello from EduResultChain API!']);
});

// Authentication routes
Route::post('login', 'Auth\AuthController@token');
Route::post('login/social', 'Auth\SocialLoginController@index');
Route::post('logout', 'Auth\AuthController@logout');
Route::post('register', 'Auth\RegistrationController@index')->middleware('registration');

Route::group(['middleware' => ['guest', 'password-reset']], function () {
    Route::post('password/remind', 'Auth\Password\RemindController@index');
    Route::post('password/reset', 'Auth\Password\ResetController@index');
});

Route::group(['middleware' => ['auth', 'registration']], function () {
    Route::post('email/resend', 'Auth\VerificationController@resend');
    Route::post('email/verify', 'Auth\VerificationController@verify');
});

// Phone verification routes (public)
Route::prefix('phone')->group(function () {
    Route::post('/generate-code', 'PhoneVerificationController@generateCode');
    Route::post('/verify', 'PhoneVerificationController@verifyPhone');
    Route::get('/status/{registrationNumber}', 'PhoneVerificationController@checkStatus');
});

// Public verification routes (no auth required)
Route::prefix('verify')->group(function () {
    Route::post('/result', 'VerificationController@publicVerify');
    Route::get('/ipfs/{ipfsHash}', 'VerificationController@retrieveFromIPFS');
});

// Protected routes
Route::middleware(['auth:api', 'verified'])->group(function () {
    // User profile routes
    Route::get('me', 'Profile\DetailsController@index');
    Route::patch('me/details', 'Profile\DetailsController@update');
    Route::patch('me/details/auth', 'Profile\AuthDetailsController@update');
    Route::post('me/avatar', 'Profile\AvatarController@update');
    Route::delete('me/avatar', 'Profile\AvatarController@destroy');
    Route::put('me/avatar/external', 'Profile\AvatarController@updateExternal');
    Route::get('me/sessions', 'Profile\SessionsController@index');

    Route::group(['middleware' => 'two-factor'], function () {
        Route::put('me/2fa', 'Profile\TwoFactorController@update');
        Route::post('me/2fa/verify', 'Profile\TwoFactorController@verify');
        Route::delete('me/2fa', 'Profile\TwoFactorController@destroy');
    });

    // Stats and settings
    Route::get('stats', 'StatsController@index');
    Route::get('/settings', 'SettingsController@index');
    Route::get('/countries', 'CountriesController@index');

    // User management
    Route::apiResource('users', 'Users\UsersController')->except('show');
    Route::get('users/{userId}', 'Users\UsersController@show');
    Route::post('users/{user}/avatar', 'Users\AvatarController@update');
    Route::put('users/{user}/avatar/external', 'Users\AvatarController@updateExternal');
    Route::delete('users/{user}/avatar', 'Users\AvatarController@destroy');

    Route::group(['middleware' => 'two-factor'], function () {
        Route::put('users/{user}/2fa', 'Users\TwoFactorController@update');
        Route::post('users/{user}/2fa/verify', 'Users\TwoFactorController@verify');
        Route::delete('users/{user}/2fa', 'Users\TwoFactorController@destroy');
    });

    Route::get('users/{user}/sessions', 'Users\SessionsController@index');
    Route::get('/sessions/{session}', 'SessionsController@show');
    Route::delete('/sessions/{session}', 'SessionsController@destroy');

    // Roles and permissions
    Route::apiResource('roles', 'Authorization\RolesController')->except('show');
    Route::get('/roles/{roleId}', 'Authorization\RolesController@show');
    Route::get('roles/{role}/permissions', 'Authorization\RolePermissionsController@show');
    Route::put('roles/{role}/permissions', 'Authorization\RolePermissionsController@update');
    Route::apiResource('permissions', 'Authorization\PermissionsController');

    // Student routes
    Route::prefix('students')->group(function () {
        // Define specific routes first
        Route::get('/with-results', 'StudentController@getStudentsWithResults');
        
        // Define generic routes
        Route::get('/', 'StudentController@index');
        Route::post('/', 'StudentController@store');
        Route::get('/{registrationNumber}', 'StudentController@show');
        Route::put('/{registrationNumber}', 'StudentController@update');
        Route::delete('/{registrationNumber}', 'StudentController@destroy');
    });

    // Form fillup routes
    Route::prefix('form-fillups')->group(function () {
        // Define specific routes first
        Route::get('/institution/{institutionId}/statistics', 'FormFillupController@getInstitutionStatistics');
        Route::get('/missing-marks', 'FormFillupController@getStudentsWithMissingMarks');
        
        // Define generic routes
        Route::get('/', 'FormFillupController@index');
        Route::post('/', 'FormFillupController@store');
        Route::get('/{rollNumber}', 'FormFillupController@show');
        Route::put('/{rollNumber}', 'FormFillupController@update');
        Route::delete('/{rollNumber}', 'FormFillupController@destroy');
    });

    // Subject routes
    Route::prefix('subjects')->group(function () {
        Route::get('/', 'SubjectController@index');
        Route::post('/', 'SubjectController@store');
        Route::get('/{subjectId}', 'SubjectController@show');
        Route::put('/{subjectId}', 'SubjectController@update');
        Route::delete('/{subjectId}', 'SubjectController@destroy');
        Route::get('/category/{category}', 'SubjectController@getByCategory');
    });

    // Exam mark routes
    Route::prefix('exam-marks')->group(function () {
        Route::get('/', 'ExamMarkController@index');
        Route::post('/', 'ExamMarkController@store');
        Route::get('/{detailId}', 'ExamMarkController@show');
        Route::put('/{detailId}', 'ExamMarkController@update');
        Route::delete('/{detailId}', 'ExamMarkController@destroy');
        Route::get('/roll/{rollNumber}', 'ExamMarkController@getByRollNumber');
        Route::post('/bulk', 'ExamMarkController@bulkCreate');
    });

    // Result routes
    Route::prefix('results')->group(function () {
        // Define specific routes first
        Route::get('/roll/{rollNumber}', 'ResultController@getByRollNumber');
        Route::post('/publish', 'ResultController@publish');
        Route::post('/unpublish', 'ResultController@unpublish');
        Route::get('/statistics', 'ResultController@getStatistics');
        Route::get('/institution-statistics', 'ResultController@getInstitutionStatistics');
        Route::post('/recalculate', 'ResultController@recalculate');
        
        // Define generic routes
        Route::get('/', 'ResultController@index');
        Route::get('/{resultId}', 'ResultController@show');
    });

    // Public result access route (no auth required)
    Route::post('/public/result', 'VerificationController@getPublicResult');

    // Result Revalidation Routes
    Route::prefix('revalidation')->group(function () {
        // Revalidation verification routes (requires auth and email verification)
        Route::middleware(['auth:api', 'verified'])->group(function () {
            // Generate verification code for revalidation
            Route::post('/verify/generate', 'RevalidationVerificationController@generateCode');
            
            // Verify code for revalidation
            Route::post('/verify/code', 'RevalidationVerificationController@verifyCode');
            
            // Check verification status
            Route::post('/verify/status', 'RevalidationVerificationController@checkVerification');
        });
        
        // Create revalidation request (requires auth and email verification)
        Route::post('/', 'ResultRevalidationController@store')
            ->middleware(['auth:api', 'verified']);
            
        // Admin/Board routes (requires auth and appropriate permissions)
        Route::middleware(['auth:api', 'verified'])->group(function () {
            // List all revalidation requests
            Route::get('/', 'ResultRevalidationController@index')
                ->middleware('permission:revalidation.manage');
                
            // View a specific revalidation request
            Route::get('/{requestId}', 'ResultRevalidationController@show')
                ->middleware('permission:revalidation.manage');
                
            // Process/review a revalidation request
            Route::post('/{requestId}/review', 'ResultRevalidationController@review');
                
            // Get revalidation statistics
            Route::get('/statistics', 'ResultRevalidationController@getStatistics')
                ->middleware('permission:revalidation.manage');
        });
    });

    // Verification routes (auth required)
    Route::prefix('verification')->group(function () {
        Route::get('/result/{resultId}', 'VerificationController@verifyResult');
        Route::get('/roll/{rollNumber}', 'VerificationController@verifyByRollNumber');
        Route::post('/update-hashes', 'VerificationController@updateHashes');
    });

    // Admin dashboard routes
    Route::prefix('admin/dashboard')->group(function () {
        Route::get('/summary', 'Admin\DashboardController@getSummary');
        Route::get('/exam-statistics', 'Admin\DashboardController@getExamStatistics');
        Route::get('/revalidation-statistics', 'Admin\DashboardController@getRevalidationStatistics');
        Route::get('/system-health', 'Admin\DashboardController@getSystemHealth');
    });
});
