<?php

namespace Tests;

use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    use DatabaseTransactions;

    public function superAdminLogin(): User
    {
        return User::factory()->create([
            'name' => 'admin',
            'email' => 'admin@yahoo.es',
            'password' => bcrypt('password'),
        ]);
    }
}
