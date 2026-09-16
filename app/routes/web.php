<?php

use App\Domain\AssessmentCalculator;
use App\Http\Controllers\AssessmentController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\CandidateController;
use App\Http\Controllers\UserController;
use App\Http\Middleware\EnsureActiveUser;
use App\Http\Requests\PreviewAssessmentRequest;
use App\Http\Resources\AssessmentResource;
use App\Models\CandidateAssessment;
use Illuminate\Support\Facades\Route;

Route::prefix('api')->group(function () {
    Route::get('csrf', fn () => response()->json(['message' => 'CSRF cookie initialized']));
    Route::post('login', [AuthController::class, 'login'])->middleware('throttle:login');
    Route::middleware(['auth', EnsureActiveUser::class])->group(function () {
        Route::get('me', [AuthController::class, 'me']);
        Route::post('logout', [AuthController::class, 'logout']);
        Route::get('users/managers', [UserController::class, 'managers']);
        Route::get('users/recruiters', [UserController::class, 'recruiters']);
        Route::apiResource('users', UserController::class)->except('show');
        Route::apiResource('candidates', CandidateController::class);
        Route::get('candidates/{candidate}/assessments', [AssessmentController::class, 'index']);
        Route::post('candidates/{candidate}/assessments', [AssessmentController::class, 'store']);
        Route::get('assessments/{assessment}', [AssessmentController::class, 'show']);
        Route::put('assessments/{assessment}', [AssessmentController::class, 'update']);
        Route::post('assessments/{assessment}/complete', [AssessmentController::class, 'complete']);
        Route::post('assessment-preview', function (PreviewAssessmentRequest $request, AssessmentCalculator $calculator) {
            $assessment = new CandidateAssessment($request->assessmentData());
            $calculator->apply($assessment);

            return new AssessmentResource($assessment);
        });
        Route::get('assessment-methodology', fn () => response()->json(['data' => config('assessment')]));
    });
});
Route::get('/healthz', fn () => response()->json(['status' => 'ok']));
Route::get('/{path?}', fn () => view('app'))->where('path','^(?!api(?:/|$)).*');
