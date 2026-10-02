<?php

use App\Http\Controllers\Api\AssessmentTypeController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\QuizApiController;
use App\Http\Controllers\Api\AdminApiController;
use App\Http\Controllers\Api\UserApiController;
use App\Http\Controllers\Api\ProfileApiController;
use App\Http\Controllers\Api\RJ45AssessmentController;
use App\Http\Controllers\Api\ImageIdentificationAssessmentController;
use App\Http\Controllers\Api\DragDropController;
use App\Http\Controllers\Api\PasswordResetController;

/*
|--------------------------------------------------------------------------
| PUBLIC ROUTES
|--------------------------------------------------------------------------
*/

Route::middleware('throttle:auth')->group(function () {
    Route::get('/test', fn() => response()->json(['ok' => true]));

    Route::controller(AdminApiController::class)->group(function () {
        Route::post('/login', 'login');
        Route::post('/mobile/login', 'mobileLogin');
        Route::post('/register', 'register');
    });

    Route::controller(PasswordResetController::class)->group(function () {
        Route::post('/forgot-password', 'sendResetLink');
        Route::post('/reset-password', 'resetPassword');
    });
});

/*
|--------------------------------------------------------------------------
| AUTHENTICATED USER ROUTES
|--------------------------------------------------------------------------
*/

Route::middleware(['auth:sanctum', 'active_user', 'throttle:api'])->group(function () {

    Route::controller(UserApiController::class)->group(function () {
        Route::get('/me', 'profile');
        Route::get('/dashboard/leaderboard', 'leaderboard');
        Route::get('/quizzes', 'quizzes');
        Route::get('/records', 'records');
        Route::get('/stats', 'stats');
        Route::get('/test', fn() => response()->json(['ok' => true]));
    });

    Route::get('assessments-type/{id}', [AssessmentTypeController::class, 'getAssessmentTypes']);


    Route::controller(QuizApiController::class)->group(function () {
        // Static before wildcard
        Route::get('/quiz/progress', 'getQuizProgress');
        Route::get('/quiz/result/{id}', 'getRecordPreview');
        Route::get('/quiz/{quiz_id}', 'getQuiz');
        //preview the record attempts of rj45 assessment
        Route::get('/records/{record}/rj45', 'showRj45');

        Route::post('/quiz/answer', 'submitAnswer');
        Route::get('/quiz/multi-result/{id}', 'MultipleChoiceResult')
            ->middleware('throttle:quiz_submit');
    });

    // COMPONENT IDENTIFICATION 
    Route::controller(ImageIdentificationAssessmentController::class)->group(function () {
        Route::get('/assessments/image-identification/{id}', 'show');
        Route::post('/assessments/image-identification/answer', 'answer');
    });

    Route::controller(RJ45AssessmentController::class)->group(function () {
        Route::get('/quizzes/{quiz}/rj45', 'show');
        Route::post('/quizzes/{quiz}/rj45/attempts', 'storeAttempt');
        Route::post('/quizzes/{quiz}/rj45/test', 'testWiring');
    });

    // DROP AND DROP DISASSEMBLE AND ASSEMBLE
    Route::controller(DragDropController::class)->group(function () {
        Route::get('/quiz/dragdrop/{id}', 'getDragDropQuiz');
        Route::post('/quiz/dragdrop/answer', 'submitDragDropAnswer');
    });

    Route::controller(AdminApiController::class)->group(function () {
        Route::post('/heartbeat', 'heartbeat');
        Route::post('/logout', 'logout');
    });

    Route::controller(ProfileApiController::class)->group(function () {
        Route::get('/profile', 'getProfile');
        Route::put('/profile/update', 'updateProfile');
        Route::post('/profile/photo', 'uploadPhoto');
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
        Route::get('/admin/records', 'studentRecords');

        Route::prefix('/admin/user')->group(function () {
            Route::get('/', 'allUsers');
            Route::put('/update/{id}', 'updateUser');
            Route::delete('/delete/{id}', 'deleteUser');
        });
    });

    Route::controller(QuizApiController::class)->group(function () {
        Route::prefix('/admin/quizzes')->group(function () {
            Route::get('/', 'allQuizzes');
            Route::get('/{id}/edit', 'editQuiz');
            Route::post('/create', 'createQuiz');
            Route::put('/{id}', 'updateQuiz');
            Route::delete('/{id}', 'deleteQuiz');
        });
    });

    Route::post('/admin/upload-photo', [ProfileApiController::class, 'uploadPhoto']);
    Route::post('/admin/update-profile', [ProfileApiController::class, 'updateProfile']);
    Route::post('/admin/create-admin', [AdminApiController::class, 'createNewAdmin']);
});
