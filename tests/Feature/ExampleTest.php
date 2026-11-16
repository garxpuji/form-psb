<?php

declare(strict_types=1);

namespace Tests\Feature;

use Tests\TestCase;

class ExampleTest extends TestCase
{
    /**
     * Root route redirects unauthenticated users to the login screen.
     */
    public function test_the_application_redirects_guest_to_login(): void
    {
        $response = $this->get('/');

        $response->assertRedirect('/login');
    }
}
