<?php

// User Controllers
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\UserApiController;
use App\Http\Controllers\Api\DragDropController;
use App\Http\Controllers\Api\ProfileApiController;
use App\Http\Controllers\Api\InputOutputController;
use App\Http\Controllers\Api\PasswordResetController;
use App\Http\Controllers\Api\AssessmentTypeController;
use App\Http\Controllers\Api\RJ45AssessmentController;
use App\Http\Controllers\Auth\EmailVerificationController;
use App\Http\Controllers\Api\HardwareProcedureController;
use App\Http\Controllers\Api\MultipleChoiceAssessmentController;
use App\Http\Controllers\Api\ImageIdentificationAssessmentController;

// Admin Controllers
use App\Http\Controllers\Admin\AdminApiController;
use App\Http\Controllers\Admin\AdminAssessmentController;

/*
|--------------------------------------------------------------------------
| PUBLIC ROUTES
|--------------------------------------------------------------------------
| 'auth' limiter: 30 requests per minute per IP.
*/

Route::middleware('throttle:auth')->group(function () {
    Route::controller(AdminApiController::class)->group(function () {
        Route::post('/login', 'login');
        Route::post('/mobile/login', 'mobileLogin');
        Route::post('/register', 'register');
    });
    Route::post('/email/resend', [EmailVerificationController::class, 'resend']);

    Route::controller(PasswordResetController::class)->group(function () {
        Route::post('/forgot-password', 'sendResetLink');
        Route::post('/reset-password', 'resetPassword');
    });
});

/*
|--------------------------------------------------------------------------
| AUTHENTICATED USER ROUTES
|--------------------------------------------------------------------------
| The 'api' middleware group already applies throttle:api.
| Submit routes add throttle:quiz_submit on top.
*/

Route::middleware(['auth:sanctum', 'active_user'])->group(function () {

    // Current user and dashboard
    Route::controller(UserApiController::class)->group(function () {
        Route::get('/me', 'profile');

        Route::get('/dashboard/overview', 'dashboardOverview');
        Route::get('/dashboard/leaderboard', 'leaderboard');

        Route::get('/records', 'records');
        Route::get('/records/{id}', 'record')->whereNumber('id');
        Route::get('/quiz/assessment/preview/{id}', 'preview')->whereNumber('id');
    });

    // COC and assessment list
    Route::controller(AssessmentTypeController::class)->group(function () {
        Route::get('/coc', 'coc');
        Route::get('/selected-assessment/{id}', 'getAssessmentTypes')->whereNumber('id');
    });

    // Multiple choice
    Route::controller(MultipleChoiceAssessmentController::class)->group(function () {
        Route::get('/multiple-choice/{quiz_id}', 'show')->whereNumber('quiz_id');
        Route::post('/multiple-choice/answer', 'submitAnswer')->middleware('throttle:quiz_submit');
        Route::get('/multiple-choice/result/{id}', 'result')->whereNumber('id');
    });

    // Input and output
    Route::controller(InputOutputController::class)->group(function () {
        Route::get('/input-output', 'index');
        Route::post('/input-output/answer', 'answer')->middleware('throttle:quiz_submit');
        Route::get('/input-output/result', 'result');
    });

    // Hardware practical
    Route::prefix('hardware')->controller(HardwareProcedureController::class)->group(function () {
        Route::get('/{quiz}', 'show')->whereNumber('quiz');
        Route::post('/{quiz}/start', 'start')->whereNumber('quiz');
        Route::post('/{quiz}/{attempt}/actions', 'action')->whereNumber(['quiz', 'attempt']);
        Route::post('/{quiz}/{attempt}/complete', 'complete')
            ->whereNumber(['quiz', 'attempt'])
            ->middleware('throttle:quiz_submit');
    });

    // Hardware record details (kept under /records to match the frontend)
    Route::get('/records/{recordId}/hardware', [HardwareProcedureController::class, 'record'])
        ->whereNumber('recordId');

    // Image identification
    Route::prefix('image-identification')
        ->controller(ImageIdentificationAssessmentController::class)
        ->group(function () {
            Route::get('/{id}', 'show')->whereNumber('id');
            Route::post('/answer', 'answer')->middleware('throttle:quiz_submit');
        });

    // RJ-45 practical
    Route::prefix('rj45')->controller(RJ45AssessmentController::class)->group(function () {
        Route::get('/{id}', 'show')->whereNumber('id');
        Route::post('/attempt/{id}', 'storeAttempt')->whereNumber('id')->middleware('throttle:quiz_submit');
        Route::post('/test/{id}', 'testWiring')->whereNumber('id');
    });

    // Drag and drop
    Route::prefix('quiz/dragdrop')->controller(DragDropController::class)->group(function () {
        Route::get('/{id}', 'getDragDropQuiz')->whereNumber('id');
        Route::post('/answer', 'submitDragDropAnswer')->middleware('throttle:quiz_submit');
    });

    // Session
    Route::controller(AdminApiController::class)->group(function () {
        Route::post('/heartbeat', 'heartbeat');
        Route::post('/logout', 'logout');
    });

    // Profile
    Route::prefix('profile')->controller(ProfileApiController::class)->group(function () {
        Route::get('/', 'getProfile');
        Route::put('/update', 'updateProfile');
        Route::post('/photo', 'uploadPhoto');
        Route::delete('/delete', 'selfDeleteAccount');
    });
});

/*
|--------------------------------------------------------------------------
| ADMIN ROUTES
|--------------------------------------------------------------------------
| auth:sanctum returns 401 if logged out.
| active_user blocks deactivated accounts.
| role:admin returns 403 for logged-in non-admins.
*/

Route::middleware(['auth:sanctum', 'active_user', 'role:admin'])->group(function () {

    Route::controller(AdminApiController::class)->group(function () {
        Route::get('/admin/dashboard', 'dashboard');
        Route::get('/admin/dashboard/assessment-leaderboard/{type}', 'assessmentLeaderboard');
        Route::get('/admin/records', 'studentRecords');

        Route::prefix('admin/user')->group(function () {
            Route::get('/', 'allUsers');
            Route::put('/update/{id}', 'updateUser')->whereNumber('id');
            Route::delete('/delete/{id}', 'deleteUser')->whereNumber('id');
        });
    });

    Route::prefix('admin/cocs')->controller(AdminAssessmentController::class)->group(function () {
        Route::get('/{id}', 'showCoc')->whereNumber('id');
        Route::put('/{id}', 'updateCoc')->whereNumber('id');
    });

    Route::prefix('admin/quizzes')->controller(AdminAssessmentController::class)->group(function () {
        Route::get('/', 'allQuizzes');
        Route::get('/assessments/{id}', 'allAssessment')->whereNumber('id');
        Route::delete('/assessments/{id}', 'deleteAssessment')->whereNumber('id');

        Route::post('/create', 'createQuiz');
        Route::put('/{id}', 'updateMultipleChoice')->whereNumber('id');
        Route::delete('/{id}', 'deleteQuiz')->whereNumber('id');
    });


    /*
     *
     * EDIT ASSESSMENT APIs 
     *
     */
    Route::prefix('admin/assessment')->group(function () {

        Route::controller(MultipleChoiceAssessmentController::class)->group(function () {
            Route::get('/multiple-choice/edit/{id}', 'edit')->whereNumber('id');
            Route::put('/multiple-choice/update/{id}', 'update');
        });

        Route::controller(ImageIdentificationAssessmentController::class)->group(function () {
            Route::get('/image-identification/edit/{id}', 'edit')->whereNumber('id');
            Route::put('/image-identification/update/{id}', 'update')->whereNumber('id');
            Route::post('/image-identification/create/{cocId}', 'store')->whereNumber('cocId');
        });

        Route::controller(InputOutputController::class)->group(function () {
            Route::get('/input-output/edit/{id}', 'edit')->whereNumber('id');
            Route::put('/input-output/update/{id}', 'update')->whereNumber('id');
        });

        Route::controller(HardwareProcedureController::class)->group(function () {
            Route::post('/hardware-procedure/create/{cocId}', 'adminStore');
            Route::get('/hardware-procedure/edit/{assessmentId}', 'edit');
            Route::put('/hardware-procedure/update/{assessmentId}', 'update');
        });

        Route::controller(RJ45AssessmentController::class)->group(function () {
            Route::post('/rj45/create/{cocId}', 'store');
            Route::get('/rj45/edit/{assessmentId}', 'edit');
            Route::put('/rj45/update/{assessmentId}', 'update');
        });

    });

    Route::post('/admin/upload-photo', [ProfileApiController::class, 'uploadPhoto']);
    Route::post('/admin/update-profile', [ProfileApiController::class, 'updateProfile']);
    Route::post('/admin/create-admin', [AdminApiController::class, 'createNewAdmin']);
});