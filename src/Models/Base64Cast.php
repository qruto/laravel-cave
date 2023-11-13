<?php

namespace App\Auth\Models;

use Illuminate\Contracts\Database\Eloquent\CastsAttributes;
use Illuminate\Database\Eloquent\Model;
use ParagonIE\ConstantTime\Base64UrlSafe;
use Webauthn\Util\Base64;

/**
 * @implements CastsAttributes<string, string>
 */
class Base64Cast implements CastsAttributes
{
    /**
     * Decode the given value with url safe base64 decoding.
     */
    public function get(Model $model, string $key, $value, array $attributes): ?string
    {
        return Base64::decode($value);
    }

    /**
     * Encode the given value with safe base64 encoding.
     */
    public function set($model, string $key, mixed $value, array $attributes): ?string
    {
        return Base64UrlSafe::encode($value);
    }
}
