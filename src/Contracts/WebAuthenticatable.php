<?php

namespace Qruto\Cave\Contracts;

use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Contracts\Auth\Authenticatable;

interface WebAuthenticatable extends Authenticatable
{
    public function passkeys(): HasMany;
}
