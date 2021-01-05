<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\ExamController;
use App\Http\Controllers\WorkingController;

Route::middleware('guest')->group(function () {
  Route::post('register', [AuthController::class, 'register']);
  Route::post('login', [AuthController::class, 'login']);
  Route::post('forgot-password', [AuthController::class, 'forgotPassword'])->name('password.email');
  Route::post('reset-password', [AuthController::class, 'resetPassword'])->name('password.update');
});

Route::middleware(['auth:sanctum', 'signed'])->get('/email/verify/{id}/{hash}', [AuthController::class, 'verifyEmail'])->name('verification.verify');

Route::prefix('levels')->middleware('auth:sanctum')->group(function () {
  Route::post('/', [ExamController::class, 'createLevels']);
  Route::put('/{level_id}', [ExamController::class, 'updateLevels']);
  Route::delete('/{level_id}', [ExamController::class, 'deleteLevels']);
  // Route::get('/', [ExamController::class, 'allLevels']);
  // Route::get('/{id}', [ExamController::class, 'showLevels']);

  Route::prefix('{level_id}/questions')->group(function () {
    Route::get('/', [ExamController::class, 'allQuestions']);
    Route::post('/', [ExamController::class, 'createQuestions']);
    Route::get('/{question_id}', [ExamController::class, 'showQuestions']);
    Route::put('/{question_id}', [ExamController::class, 'updateQuestions']);
    Route::delete('/{question_id}', [ExamController::class, 'deleteQuestions']);
  });
  
  Route::prefix('{level_id}/answer-sheets')->group(function () {
    Route::post('/', [WorkingController::class, 'createAnswerSheets']);
    Route::patch('/{answer_sheet_id}', [WorkingController::class, 'finishAnswerSheets']);

    Route::prefix('{answer_sheet_id}/questions')->group(function () {
      Route::get('/', [WorkingController::class, 'allQuestions']);
      Route::get('/{question_id}', [WorkingController::class, 'showQuestions']);

      Route::prefix('{question_id}/answers')->group(function () {
        Route::post('/', [WorkingController::class, 'submitAnswers']);
      });
    });
  });
});

// Route::prefix('case-studies')->group(function () {
//   Route::post('/', [ExamController::class, 'createCaseStudies']);
//   Route::put('/{case_study_id}', [ExamController::class, 'updateCaseStudies']);
//   Route::delete('/{case_study_id}', [ExamController::class, 'deleteCaseStudies']);
// });