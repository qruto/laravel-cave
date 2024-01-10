<?php

namespace Qruto\Cave;

use Illuminate\Http\Request;

interface AuthRateLimiter
{
    /**
     * Get the number of attempts for the given key.
     *
     * @return mixed
     */
    public function attempts(Request $request);

    /**
     * Determine if the user has too many failed login attempts.
     *
     * @return bool
     */
    public function tooManyAttempts(Request $request);

    /**
     * Increment the login attempts for the user.
     *
     * @return void
     */
    public function increment(Request $request);

    /**
     * Determine the number of seconds until logging in is available again.
     *
     * @return int
     */
    public function availableIn(Request $request);

    /**
     * Clear the login locks for the given user credentials.
     *
     * @return void
     */
    public function clear(Request $request);
}
