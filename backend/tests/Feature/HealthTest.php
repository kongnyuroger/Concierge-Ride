<?php

test('health endpoint reports ok', function () {
    $response = $this->getJson('/api/health');

    $response->assertOk()->assertJson(['status' => 'ok']);
});
