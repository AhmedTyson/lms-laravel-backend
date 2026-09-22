<?php

use Illuminate\Support\Facades\Route;
use Modules\Courses\Http\Controllers\CoursesController;

// Course Routes

Route::get('/courses', [CoursesController::class, 'getAllCourses'])->middleware('throttle:api');
Route::get('/courses/{course}', [CoursesController::class, 'show'])->middleware('throttle:api');
Route::post('/courses', [CoursesController::class, 'store'])->middleware('throttle:api');
Route::post('/courses/{course}/publish', [CoursesController::class, 'publish'])->middleware('throttle:api');
Route::post('/courses/{course}/archive', [CoursesController::class, 'archive'])->middleware('throttle:api');
Route::post('/courses/{course}/duplicate', [CoursesController::class, 'duplicate'])->middleware('throttle:api');
Route::post('/courses/{course}/lesson', [CoursesController::class, 'store'])->middleware('throttle:api');
Route::put('/courses/{course}/lesson/{lesson}', [CoursesController::class, 'update'])->middleware('throttle:api');
Route::patch('/courses/{course}/lesson/{lesson}', [CoursesController::class, 'update'])->middleware('throttle:api');
Route::delete('/courses/{course}/lesson/{lesson}', [CoursesController::class, 'destroy'])->middleware('throttle:api');
Route::put('/courses/{course}', [CoursesController::class, 'update'])->middleware('throttle:api');
Route::patch('/courses/{course}', [CoursesController::class, 'update'])->middleware('throttle:api');
Route::delete('/courses/{course}', [CoursesController::class, 'destroy'])->middleware('throttle:api');
