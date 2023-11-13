<?php

use Illuminate\Support\Facades\Route;
use Qruto\Cave\Cave;
use Qruto\Cave\Features;
use Qruto\Cave\Http\Controllers\AuthenticatedSessionController;
use Qruto\Cave\Http\Controllers\AuthenticatedSessionOptionsController;
use Qruto\Cave\Http\Controllers\ConfirmablePasswordController;
use Qruto\Cave\Http\Controllers\ConfirmedPasswordStatusController;
use Qruto\Cave\Http\Controllers\ConfirmedTwoFactorAuthenticationController;
use Qruto\Cave\Http\Controllers\EmailVerificationNotificationController;
use Qruto\Cave\Http\Controllers\EmailVerificationPromptController;
use Qruto\Cave\Http\Controllers\NewPasswordController;
use Qruto\Cave\Http\Controllers\PasswordController;
use Qruto\Cave\Http\Controllers\PasswordResetLinkController;
use Qruto\Cave\Http\Controllers\ProfileInformationController;
use Qruto\Cave\Http\Controllers\RecoveryCodeController;
use Qruto\Cave\Http\Controllers\RegisteredUserController;
use Qruto\Cave\Http\Controllers\TwoFactorAuthenticatedSessionController;
use Qruto\Cave\Http\Controllers\TwoFactorAuthenticationController;
use Qruto\Cave\Http\Controllers\TwoFactorQrCodeController;
use Qruto\Cave\Http\Controllers\TwoFactorSecretKeyController;
use Qruto\Cave\Http\Controllers\VerifyEmailController;
use Qruto\Cave\RoutePath;

if (Cave::$registersRoutes) {
    Route::group([
        'namespace' => 'Qruto\Cave\Http\Controllers',
        'domain' => config('cave.domain', null),
        'prefix' => config('cave.prefix'),
    ], function () {
        Route::group(['middleware' => config('cave.middleware', ['web'])], function () {
            $enableViews = config('cave.views', true);

            // Authentication...
            if ($enableViews) {
                Route::get(RoutePath::for('auth', '/auth'), [AuthenticatedSessionController::class, 'create'])
                    ->middleware(['guest:'.config('cave.guard')])
                    ->name('auth');
            }

            $limiter = config('cave.limiters.login');
            $twoFactorLimiter = config('cave.limiters.two-factor');
            $verificationLimiter = config('cave.limiters.verification', '6,1');

            Route::get(RoutePath::for('auth', '/auth').'/options', [AuthenticatedSessionOptionsController::class, 'create'])
                ->middleware(array_filter([
                    'guest:'.config('cave.guard'),
                    $limiter ? 'throttle:'.$limiter : null,
                ]));

//
            Route::post(RoutePath::for('login', '/login'), [AuthenticatedSessionController::class, 'store'])
                ->middleware(array_filter([
                    'guest:'.config('cave.guard'),
                    $limiter ? 'throttle:'.$limiter : null,
                ]));
//
//    Route::post(RoutePath::for('logout', '/logout'), [AuthenticatedSessionController::class, 'destroy'])
//        ->name('logout');
//
//    // Password Reset...
//    if (Features::enabled(Features::resetPasswords())) {
//        if ($enableViews) {
//            Route::get(RoutePath::for('password.request', '/forgot-password'), [PasswordResetLinkController::class, 'create'])
//                ->middleware(['guest:'.config('cave.guard')])
//                ->name('password.request');
//
//            Route::get(RoutePath::for('password.reset', '/reset-password/{token}'), [NewPasswordController::class, 'create'])
//                ->middleware(['guest:'.config('cave.guard')])
//                ->name('password.reset');
//        }
//
//        Route::post(RoutePath::for('password.email', '/forgot-password'), [PasswordResetLinkController::class, 'store'])
//            ->middleware(['guest:'.config('cave.guard')])
//            ->name('password.email');
//
//        Route::post(RoutePath::for('password.update', '/reset-password'), [NewPasswordController::class, 'store'])
//            ->middleware(['guest:'.config('cave.guard')])
//            ->name('password.update');
//    }
//
//    // Registration...
//    if (Features::enabled(Features::registration())) {
//        if ($enableViews) {
//            Route::get(RoutePath::for('register', '/register'), [RegisteredUserController::class, 'create'])
//                ->middleware(['guest:'.config('cave.guard')])
//                ->name('register');
//        }
//
//        Route::post(RoutePath::for('register', '/register'), [RegisteredUserController::class, 'store'])
//            ->middleware(['guest:'.config('cave.guard')]);
//    }
//
//    // Email Verification...
//    if (Features::enabled(Features::emailVerification())) {
//        if ($enableViews) {
//            Route::get(RoutePath::for('verification.notice', '/email/verify'), [EmailVerificationPromptController::class, '__invoke'])
//                ->middleware([config('cave.auth_middleware', 'auth').':'.config('cave.guard')])
//                ->name('verification.notice');
//        }
//
//        Route::get(RoutePath::for('verification.verify', '/email/verify/{id}/{hash}'), [VerifyEmailController::class, '__invoke'])
//            ->middleware([config('cave.auth_middleware', 'auth').':'.config('cave.guard'), 'signed', 'throttle:'.$verificationLimiter])
//            ->name('verification.verify');
//
//        Route::post(RoutePath::for('verification.send', '/email/verification-notification'), [EmailVerificationNotificationController::class, 'store'])
//            ->middleware([config('cave.auth_middleware', 'auth').':'.config('cave.guard'), 'throttle:'.$verificationLimiter])
//            ->name('verification.send');
//    }
//
//    // Profile Information...
//    if (Features::enabled(Features::updateProfileInformation())) {
//        Route::put(RoutePath::for('user-profile-information.update', '/user/profile-information'), [ProfileInformationController::class, 'update'])
//            ->middleware([config('cave.auth_middleware', 'auth').':'.config('cave.guard')])
//            ->name('user-profile-information.update');
//    }
//
//    // Passwords...
//    if (Features::enabled(Features::updatePasswords())) {
//        Route::put(RoutePath::for('user-password.update', '/user/password'), [PasswordController::class, 'update'])
//            ->middleware([config('cave.auth_middleware', 'auth').':'.config('cave.guard')])
//            ->name('user-password.update');
//    }
//
//    // Password Confirmation...
//    if ($enableViews) {
//        Route::get(RoutePath::for('password.confirm', '/user/confirm-password'), [ConfirmablePasswordController::class, 'show'])
//            ->middleware([config('cave.auth_middleware', 'auth').':'.config('cave.guard')]);
//    }
//
//    Route::get(RoutePath::for('password.confirmation', '/user/confirmed-password-status'), [ConfirmedPasswordStatusController::class, 'show'])
//        ->middleware([config('cave.auth_middleware', 'auth').':'.config('cave.guard')])
//        ->name('password.confirmation');
//
//    Route::post(RoutePath::for('password.confirm', '/user/confirm-password'), [ConfirmablePasswordController::class, 'store'])
//        ->middleware([config('cave.auth_middleware', 'auth').':'.config('cave.guard')])
//        ->name('password.confirm');
        });
    });
}
