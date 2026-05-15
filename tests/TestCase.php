<?php

namespace Tests;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    use RefreshDatabase;

    protected function loginAs($user)
    {
        return $this->actingAs($user, 'sanctum');
    }

    protected function loginAsRole($role = 'pegawai')
    {
        $user = User::factory()->{$role}()->create();
        return $this->loginAs($user);
    }

    protected function getTokenFor($user)
    {
        return $user->createToken('test-token')->plainTextToken;
    }
}
