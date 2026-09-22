<?php

// Course Routes

Route::get('/courses', [CoursesController::class, 'index'])->middleware('throttle:');
Route::get('/courses/{course}', [CoursesController::class, 'show'])->middleware('throttle:');
Route::post('/courses', [CoursesController::class, 'store'])->middleware('throttle:');
Route::post('/courses/{course}/publish', [CoursesController::class, 'publish'])->middleware('throttle:');
Route::post('/courses/{course}/archive', [CoursesController::class, 'archive'])->middleware('throttle:');
Route::post('/courses/{course}/duplicate', [CoursesController::class, 'duplicate'])->middleware('throttle:');
Route::post('/courses/{course}/lesson', [CoursesController::class, 'store'])->middleware('throttle:');
Route::put('/courses/{course}/lesson/{lesson}', [CoursesController::class, 'update'])->middleware('throttle:');
Route::patch('/courses/{course}/lesson/{lesson}', [CoursesController::class, 'update'])->middleware('throttle:');
Route::delete('/courses/{course}/lesson/{lesson}', [CoursesController::class, 'destroy'])->middleware('throttle:');
Route::put('/courses/{course}', [CoursesController::class, 'update'])->middleware('throttle:');
Route::patch('/courses/{course}', [CoursesController::class, 'update'])->middleware('throttle:');
Route::delete('/courses/{course}', [CoursesController::class, 'destroy'])->middleware('throttle:');
