<?php

// Init skeleton gate: all nine business modules registered, zero business routes inside them.

test('all nine modules are enabled', function () {
    $modules = ['Auth', 'AccessManagement', 'Courses', 'Enrollment', 'Assignments', 'Quizzes', 'Progress', 'Notifications', 'Reporting'];

    foreach ($modules as $name) {
        expect(app('modules')->has($name))->toBeTrue("$name module missing");
        expect(app('modules')->isEnabled($name))->toBeTrue("$name module disabled");
    }
});

test('modules expose no business routes yet', function () {
    $routes = collect(app('router')->getRoutes()->getRoutes())
        ->map(fn ($r) => $r->uri())
        ->filter(fn ($uri) => str_starts_with($uri, 'api/'));

    // Only the shared health check may exist at init.
    expect($routes->values()->all())->toBe(['api/health']);
});
