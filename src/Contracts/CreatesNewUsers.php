<?php

namespace Qruto\Cave\Contracts;

use Illuminate\Foundation\Auth\User;

interface CreatesNewUsers
{
    /**
     * Validate and create a newly registered user.
     */
    public function create(array $input, User $user): User;
}
