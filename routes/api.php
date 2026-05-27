<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\QuizApiController;
use App\Http\Controllers\Api\AdminApiController;
use App\Http\Controllers\Api\UserApiController;
use App\Http\Controllers\Api\ProfileApiController;
use App\Http\Controllers\Api\PasswordResetController;

/*
|--------------------------------------------------------------------------
| PUBLIC ROUTES
|--------------------------------------------------------------------------
*/

Route::middleware('throttle:auth')->group(function () {
    Route::get('/test', fn() => response()->json(['ok' => true]));

    Route::controller(AdminApiController::class)->group(function () {
        Route::post('/login',        'login');
        Route::post('/mobile/login', 'mobileLogin');
        Route::post('/register',     'register');
    });

    Route::controller(PasswordResetController::class)->group(function () {
        Route::post('/forgot-password', 'sendResetLink');
        Route::post('/reset-password',  'resetPassword');
    });
});

/*
|--------------------------------------------------------------------------
| AUTHENTICATED USER ROUTES
|--------------------------------------------------------------------------
*/

Route::middleware(['auth:sanctum', 'active_user', 'throttle:api'])->group(function () {

    Route::controller(UserApiController::class)->group(function () {
        Route::get('/me',                    'profile');
        Route::get('/dashboard/leaderboard', 'leaderboard');
        Route::get('/quizzes',               'quizzes');
        Route::get('/records',               'records');
        Route::get('/stats',                 'stats');
    });

    Route::controller(QuizApiController::class)->group(function () {
        // ✅ Static before wildcard
        Route::get('/quiz/progress',    'getQuizProgress');
        Route::get('/quiz/result/{id}', 'getQuizResult');
        Route::get('/quiz/{quiz_id}',   'getQuiz');

        Route::post('/quiz/answer', 'submitAnswer');
        Route::post('/quiz/result', 'submitQuizResult')
            ->middleware('throttle:quiz_submit');
    });

    Route::controller(AdminApiController::class)->group(function () {
        Route::post('/heartbeat', 'heartbeat');
        Route::post('/logout',    'logout');
    });

    Route::controller(ProfileApiController::class)->group(function () {
        Route::get('/profile',          'getProfile');
        Route::put('/profile/update',   'updateProfile');
        Route::post('/profile/photo',   'uploadPhoto');
        Route::delete('/profile/delete', 'selfDeleteAccount');
    });
});

/*
|--------------------------------------------------------------------------
| ADMIN ROUTES
|--------------------------------------------------------------------------
*/

Route::middleware(['auth:sanctum', 'role:admin', 'throttle:api'])->group(function () {

    Route::controller(AdminApiController::class)->group(function () {
        Route::get('/admin/dashboard', 'dashboard');
        Route::get('/admin/records',   'studentRecords');

        Route::prefix('/admin/quizzes')->group(function () {
            Route::get('/',          'allQuizzes');
            Route::get('/{id}/edit', 'editQuiz');
            Route::post('/create',   'createQuiz');
            Route::put('/{id}',      'updateQuiz');
            Route::delete('/{id}',   'deleteQuiz');
        });


        Route::prefix('/admin/user')->group(function () {
            Route::get('/',           'allUsers');
            Route::put('/update/{id}', 'updateUser');
            Route::delete('/delete/{id}', 'deleteUser');
        });
    });

    Route::post('/admin/upload-photo', [ProfileApiController::class, 'uploadPhoto']);
    Route::post('/admin/update-profile', [ProfileApiController::class, 'updateProfile']);
});
