<?php

namespace Qruto\Cave\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Str;
use Qruto\Cave\Contracts\ProfileInformationUpdatedResponse;
use Qruto\Cave\Contracts\UpdatesUserProfileInformation;
use Qruto\Cave\Fortify;

class ProfileInformationController extends Controller
{
    /**
     * Update the user's profile information.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Qruto\Cave\Contracts\UpdatesUserProfileInformation  $updater
     * @return \Qruto\Cave\Contracts\ProfileInformationUpdatedResponse
     */
    public function update(Request $request,
                           UpdatesUserProfileInformation $updater)
    {
        if (config('fortify.lowercase_usernames')) {
            $request->merge([
                Fortify::username() => Str::lower($request->{Fortify::username()}),
            ]);
        }

        $updater->update($request->user(), $request->all());

        return app(ProfileInformationUpdatedResponse::class);
    }
}
