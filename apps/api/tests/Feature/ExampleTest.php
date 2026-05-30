<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ExampleTest extends TestCase
{
    use RefreshDatabase;

    public function test_setup_page_is_available_when_no_users_exist(): void
    {
        $response = $this->get('/setup');

        $response->assertStatus(200);
    }
}
