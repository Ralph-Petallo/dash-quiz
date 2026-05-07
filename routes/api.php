<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\QuizApiController;
use App\Http\Controllers\Api\AdminApiController;
use App\Http\Controllers\Api\UserApiController;
use App\Http\Controllers\Api\ProfileApiController;
use App\Http\Controllers\Api\PasswordResetController;
use App\Models\Dasher;

/*
| Public Routes
*/

Route::get('/test', fn() => response()->json(
    [
        'ok' => true,
    ]
)); // for test route

Route::post('/login', [AdminApiController::class, 'login']);
Route::post('/mobile/login', [AdminApiController::class, 'mobileLogin']);

Route::post('/register', [AdminApiController::class, 'register']);

Route::post('/forgot-password', [PasswordResetController::class, 'sendResetLink']);
Route::post('/reset-password', [PasswordResetController::class, 'resetPassword']);

/*
| Authenticated Routes (Dashers/Users)
*/
Route::middleware(['auth:sanctum', 'active_user'])->group(function () {

    Route::get('/me', [UserApiController::class, 'profile']);
    // offline / online checker
    Route::post('/heartbeat', [AdminApiController::class, 'heartbeat']);

    // Dashboard & Leaderboard
    Route::get('/dashboard/leaderboard', [UserApiController::class, 'leaderboard']);

    // Quizzes
    Route::get('/quizzes', [UserApiController::class, 'quizzes']);
    Route::get('/quiz/{quiz_id}', [QuizApiController::class, 'getQuiz']);
    Route::get('/quiz/progress', [QuizApiController::class, 'getQuizProgress']);
    Route::post('/quiz/answer', [QuizApiController::class, 'submitAnswer']);
    Route::post('/quiz/result', [QuizApiController::class, 'submitQuizResult']);
    Route::get('/quiz/record/{id}', [QuizApiController::class, 'getQuizRecord']);

    // Records
    Route::get('/records', [UserApiController::class, 'records']);

    // Profile
    Route::put('/profile/update', [ProfileApiController::class, 'updateProfile']);
    Route::post('/profile/photo', [ProfileApiController::class, 'uploadPhoto']);
    Route::delete('/profile/delete', [ProfileApiController::class, 'selfDeleteAccount']);

    // Logout
    Route::post('/logout', [AdminApiController::class, 'logout']);
});


/*
| Admin Specific Routes
*/
// Ensure your 'admin' guard is configured in config/auth.php
Route::middleware(['auth:sanctum', 'role:admin'])->group(function () {

    Route::get('/admin/dashboard', [AdminApiController::class, 'dashboard']);

    Route::get('/admin/quizzes', [AdminApiController::class, 'allQuizzes']);
    Route::get('/admin/quizzes/{id}/edit', [AdminApiController::class, 'editQuiz']);
    Route::put('/admin/quizzes/{id}', [AdminApiController::class, 'updateQuiz']);
    Route::post('/admin/quizzes/create', [AdminApiController::class, 'createQuiz']);
    Route::delete('/admin/quizzes/{id}', [AdminApiController::class, 'deleteQuiz']);

    Route::get('/admin/users', [AdminApiController::class, 'allUsers']);
    Route::delete('/admin/user/delete/{id}', [AdminApiController::class, 'deleteUser']);
    Route::put('/admin/user/update/{id}', [AdminApiController::class, 'updateUser']);

    Route::get('/admin/records', [AdminApiController::class, 'studentRecords']);
});
