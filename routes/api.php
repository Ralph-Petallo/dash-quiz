<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\QuizApiController;
use App\Http\Controllers\Api\AdminApiController;
use App\Http\Controllers\Api\UserApiController;
use App\Http\Controllers\Api\ProfileApiController;
use App\Http\Controllers\Api\PasswordResetController;

/*
|--------------------------------------------------------------------------
| PUBLIC ROUTES (light protection)
|--------------------------------------------------------------------------
*/

Route::middleware('throttle:30,1')->group(function () {

    Route::get('/test', fn () => response()->json(['ok' => true]));

    Route::post('/login', [AdminApiController::class, 'login']);
    Route::post('/mobile/login', [AdminApiController::class, 'mobileLogin']);

    Route::post('/register', [AdminApiController::class, 'register']);

    Route::post('/forgot-password', [PasswordResetController::class, 'sendResetLink']);
    Route::post('/reset-password', [PasswordResetController::class, 'resetPassword']);

});


/*
|--------------------------------------------------------------------------
| AUTHENTICATED USER ROUTES (medium protection)
|--------------------------------------------------------------------------
*/

Route::middleware([
    'auth:sanctum',
    'active_user',
    'throttle:60,1'
])->group(function () {

    Route::get('/me', [UserApiController::class, 'profile']);

    Route::post('/heartbeat', [AdminApiController::class, 'heartbeat'])
        ->middleware('throttle:20,1');

    Route::get('/dashboard/leaderboard', [UserApiController::class, 'leaderboard']);

    Route::get('/quizzes', [UserApiController::class, 'quizzes']);

    Route::get('/quiz/{quiz_id}', [QuizApiController::class, 'getQuiz']);

    Route::get('/quiz/progress', [QuizApiController::class, 'getQuizProgress']);

    Route::post('/quiz/answer', [QuizApiController::class, 'submitAnswer'])
        ->middleware('throttle:30,1');

    Route::post('/quiz/result', [QuizApiController::class, 'submitQuizResult'])
        ->middleware('throttle:10,1'); // IMPORTANT (prevents spam submit)

    Route::get('/quiz/result/{id}', [QuizApiController::class, 'getQuizResult']);

    Route::get('/records', [UserApiController::class, 'records'])
        ->middleware('throttle:60,1');

    Route::put('/profile/update', [ProfileApiController::class, 'updateProfile'])
        ->middleware('throttle:10,1');

    Route::post('/profile/photo', [ProfileApiController::class, 'uploadPhoto'])
        ->middleware('throttle:10,1');

    Route::delete('/profile/delete', [ProfileApiController::class, 'selfDeleteAccount'])
        ->middleware('throttle:5,1');

    Route::post('/logout', [AdminApiController::class, 'logout']);
});


/*
|--------------------------------------------------------------------------
| ADMIN ROUTES (strict protection)
|--------------------------------------------------------------------------
*/

Route::middleware([
    'auth:sanctum',
    'role:admin',
    'throttle:120,1'
])->group(function () {

    Route::get('/admin/dashboard', [AdminApiController::class, 'dashboard']);

    Route::get('/admin/quizzes', [QuizApiController::class, 'allQuizzes']);

    Route::get('/admin/quizzes/{id}/edit', [AdminApiController::class, 'editQuiz']);

    Route::put('/admin/quizzes/{id}', [AdminApiController::class, 'updateQuiz'])
        ->middleware('throttle:30,1');

    Route::post('/admin/quizzes/create', [AdminApiController::class, 'createQuiz'])
        ->middleware('throttle:20,1');

    Route::delete('/admin/quizzes/{id}', [AdminApiController::class, 'deleteQuiz'])
        ->middleware('throttle:10,1');

    Route::get('/admin/users', [AdminApiController::class, 'allUsers']);

    Route::delete('/admin/user/delete/{id}', [AdminApiController::class, 'deleteUser'])
        ->middleware('throttle:10,1');

    Route::put('/admin/user/update/{id}', [AdminApiController::class, 'updateUser'])
        ->middleware('throttle:20,1');

    Route::get('/admin/records', [AdminApiController::class, 'studentRecords']);
});