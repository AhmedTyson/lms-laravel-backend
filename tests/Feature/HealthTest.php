<?php

test('health endpoint returns ok', function () {
    $response = $this->getJson('/api/health');

    $response->assertOk()->assertJson(['ok' => true]);
});
