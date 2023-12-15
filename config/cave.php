<?php

use App\Providers\RouteServiceProvider;
use Cose\Algorithms;
use Qruto\Cave\Features;

return [

    /*
    |--------------------------------------------------------------------------
    | Fortify Guard
    |--------------------------------------------------------------------------
    |
    | Here you may specify which authentication guard Fortify will use while
    | authenticating users. This value should correspond with one of your
    | guards that is already present in your "auth" configuration file.
    |
    */

    'guard' => 'web',

    /*
    |--------------------------------------------------------------------------
    | Fortify Password Broker
    |--------------------------------------------------------------------------
    |
    | Here you may specify which password broker Fortify can use when a user
    | is resetting their password. This configured value should match one
    | of your password brokers setup in your "auth" configuration file.
    |
    */

    'passwords' => 'users',

    /*
    |--------------------------------------------------------------------------
    | Username / Email
    |--------------------------------------------------------------------------
    |
    | This value defines which model attribute should be considered as your
    | application's "username" field. Typically, this might be the email
    | address of the users but you are free to change this value here.
    |
    | Out of the box, Fortify expects forgot password and reset password
    | requests to have a field named 'email'. If the application uses
    | another name for the field you may define it below as needed.
    |
    */

    'username' => 'email',

    'email' => 'email',

    /*
    |--------------------------------------------------------------------------
    | Lowercase Usernames
    |--------------------------------------------------------------------------
    |
    | This value defines whether usernames should be lowercased before saving
    | them in the database, as some database system string fields are case
    | sensitive. You may disable this for your application if necessary.
    |
    */

    'lowercase_usernames' => true,

    /*
    |--------------------------------------------------------------------------
    | Home Path
    |--------------------------------------------------------------------------
    |
    | Here you may configure the path where users will get redirected during
    | authentication or password reset when the operations are successful
    | and the user is authenticated. You are free to change this value.
    |
    */

    'home' => RouteServiceProvider::HOME,

    /*
    |--------------------------------------------------------------------------
    | Fortify Routes Prefix / Subdomain
    |--------------------------------------------------------------------------
    |
    | Here you may specify which prefix Fortify will assign to all the routes
    | that it registers with the application. If necessary, you may change
    | subdomain under which all of the Fortify routes will be available.
    |
    */

    'prefix' => '',

    'domain' => null,

    /*
    |--------------------------------------------------------------------------
    | Fortify Routes Middleware
    |--------------------------------------------------------------------------
    |
    | Here you may specify which middleware Fortify will assign to the routes
    | that it registers with the application. If necessary, you may change
    | these middleware but typically this provided default is preferred.
    |
    */

    'middleware' => ['web'],

    /*
    |--------------------------------------------------------------------------
    | Rate Limiting
    |--------------------------------------------------------------------------
    |
    | By default, Fortify will throttle logins to five requests per minute for
    | every email and IP address combination. However, if you would like to
    | specify a custom rate limiter to call then you may specify it here.
    |
    */

    'limiters' => [
        'login' => 'login',
        'two-factor' => 'two-factor',
    ],

    /*
    |--------------------------------------------------------------------------
    | Register View Routes
    |--------------------------------------------------------------------------
    |
    | Here you may specify if the routes returning views should be disabled as
    | you may not need them when building your own application. This may be
    | especially true if you're writing a custom single-page application.
    |
    */

    'views' => true,

    /*
    |--------------------------------------------------------------------------
    | Features
    |--------------------------------------------------------------------------
    |
    | Some of the Fortify features are optional. You may disable the features
    | by removing them from this array. You're free to only remove some of
    | these features or you can even remove all of these if you need to.
    |
    */

    'features' => [
        Features::registration(),
        Features::resetPasswords(),
        // Features::emailVerification(),
        Features::updateProfileInformation(),
        Features::updatePasswords(),
        Features::twoFactorAuthentication([
            'confirm' => true,
            'confirmPassword' => true,
            // 'window' => 0,
        ]),
    ],

    'public_key_credential_algorithms' => [
        Algorithms::COSE_ALGORITHM_ES256,
        Algorithms::COSE_ALGORITHM_ES256K,
        Algorithms::COSE_ALGORITHM_ES384,
        Algorithms::COSE_ALGORITHM_ES512,
        Algorithms::COSE_ALGORITHM_RS256,
        Algorithms::COSE_ALGORITHM_RS384,
        Algorithms::COSE_ALGORITHM_RS512,
        Algorithms::COSE_ALGORITHM_PS256,
        Algorithms::COSE_ALGORITHM_PS384,
        Algorithms::COSE_ALGORITHM_PS512,
        Algorithms::COSE_ALGORITHM_ED256,
        Algorithms::COSE_ALGORITHM_ED512,
    ],

    /*
    |--------------------------------------------------------------------------
    | User presence and verification
    |--------------------------------------------------------------------------
    |
    | Most authenticators and smartphones will ask the user to actively verify
    | themselves for log in. Use "required" to always ask verify, "preferred"
    | to ask when possible, and "discouraged" to just ask for user presence.
    |
    | See https://www.w3.org/TR/webauthn/#enum-userVerificationRequirement
    |
    | Supported: "required", "preferred", "discouraged".
    |
    */

    'user_verification' => 'preferred',

    /*
     |--------------------------------------------------------------------------
     | Resident Key
     |--------------------------------------------------------------------------
     |
     | Resident key is a credential that is stored on the authenticator itself.
     | This means that the credential is not sent to the server during
     | authentication, and thus can't be phished or intercepted.
     |
     | See https://www.w3.org/TR/webauthn/#enum-residentKeyRequirement
     |
     | Supported: "required", "preferred", "discouraged". null for default which
     | is "discouraged" for cross-platform devices and "preferred" for platform.
     */
    'resident_key' => null,

    /*
    |--------------------------------------------------------------------------
    | Credentials Attachment.
    |--------------------------------------------------------------------------
    |
    | Authentication can be tied to the current device (like when using Windows
    | Hello or Touch ID) or a cross-platform device (like USB Key). When this
    | is "null" the user will decide where to store his authentication info.
    |
    | See https://www.w3.org/TR/webauthn/#enum-attachment
    |
    | Supported: "null", "cross-platform", "platform".
    |
    */

    'attachment_mode' => null,

    'extensions' => [
        //        'loc' => true,
        //        'txAuthSimple' => 'This is custom text',
        //        'credProps' => true,
        //        'uvm' => true,
    ],

    'timeout' => 60000,

    /*
    |--------------------------------------------------------------------------
    | Webauthn Attestation Conveyance
    |--------------------------------------------------------------------------
    |
    | This parameter specify the preference regarding the attestation conveyance
    | during credential generation.
    | See https://www.w3.org/TR/webauthn/#enum-attestation-convey
    |
    | Supported: "none", "indirect", "direct", "enterprise".
    */

    'attestation_conveyance' => 'none',

];
