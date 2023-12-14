<?php

namespace Qruto\Cave\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Qruto\Cave\Contracts\WebAuthenticatable as WebAuthenticatableContracts;

class User extends Authenticatable implements WebAuthenticatableContracts
{
    use WebAuthenticatable;
}
