<?php

use Illuminate\Support\Facades\Route;
use Modules\Courses\Http\Controllers\CoursesController;

// Course Routes — only implemented endpoints stay registered.
// Write endpoints land with their slice (docs/routes/courses.md).

Route::get('/courses', [CoursesController::class, 'getAllCourses'])->middleware('throttle:api');
