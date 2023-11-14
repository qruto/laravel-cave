<?php

namespace Qruto\Cave\Models;

use Illuminate\Contracts\Database\Eloquent\CastsAttributes;
use Illuminate\Database\Eloquent\Model;
use Webauthn\TrustPath\TrustPath;
use Webauthn\TrustPath\TrustPathLoader;

/**
 * @implements CastsAttributes<TrustPath,string>
 */
class TrustPathCast implements CastsAttributes
{
    /**
     * Cast the given value.
     *
     * @param  Model  $model
     * @param  mixed  $value
     */
    public function get($model, string $key, $value, array $attributes): ?TrustPath
    {
        return $value !== null ? TrustPathLoader::loadTrustPath(json_decode($value, true, flags: JSON_THROW_ON_ERROR)) : null;
    }

    /**
     * Prepare the given value for storage.
     *
     * @param  Model  $model
     * @param  string|null  $value
     */
    public function set($model, string $key, mixed $value, array $attributes): ?string
    {
        return json_encode($value, flags: JSON_THROW_ON_ERROR);
    }
}
