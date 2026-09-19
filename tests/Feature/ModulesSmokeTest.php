<?php

// Init skeleton gate: all nine business modules registered, zero business routes inside them.

test('all nine modules are enabled', function () {
    $modules = ['Auth', 'AccessManagement', 'Courses', 'Enrollment', 'Assignments', 'Quizzes', 'Progress', 'Notifications', 'Reporting'];

    foreach ($modules as $name) {
        expect(app('modules')->has($name))->toBeTrue("$name module missing");
        expect(app('modules')->isEnabled($name))->toBeTrue("$name module disabled");
    }
});

test('modules expose valid registered routes', function () {
    $routes = collect(app('router')->getRoutes()->getRoutes())
        ->map(fn ($r) => $r->uri())
        ->filter(fn ($uri) => str_starts_with($uri, 'api/'));

    expect($routes->contains('api/health'))->toBeTrue();
    expect($routes->contains('api/auth/register'))->toBeTrue();
    expect($routes->contains('api/auth/login'))->toBeTrue();
});
