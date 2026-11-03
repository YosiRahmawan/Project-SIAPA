<?php

namespace Tests\Feature;

use Tests\TestCase;

class ExampleTest extends TestCase
{
    public function test_halaman_awal_mengarahkan_tamu_ke_login(): void
    {
        $this->get('/')->assertRedirect('/login');
    }

    public function test_health_check(): void
    {
        $this->get('/up')->assertOk();
    }
}
