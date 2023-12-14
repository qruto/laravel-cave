<?php

namespace Qruto\Cave\Contracts;

use Qruto\Cave\Models\User;

interface CreatesNewUsers
{
    /**
     * Validate and create a newly registered user.
     */
    public function create(array $input, User $user): User;
}
