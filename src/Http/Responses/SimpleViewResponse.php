<?php

namespace Qruto\Cave\Http\Responses;

use Illuminate\Contracts\Support\Responsable;
use Qruto\Cave\Contracts\AuthViewResponse;
use Qruto\Cave\Contracts\ConfirmPasskeyViewResponse;
use Qruto\Cave\Contracts\RegisterViewResponse;
use Qruto\Cave\Contracts\RequestPasswordResetLinkViewResponse;
use Qruto\Cave\Contracts\ResetPasswordViewResponse;
use Qruto\Cave\Contracts\TwoFactorChallengeViewResponse;
use Qruto\Cave\Contracts\VerifyEmailViewResponse;

class SimpleViewResponse implements AuthViewResponse, ConfirmPasskeyViewResponse, RegisterViewResponse, RequestPasswordResetLinkViewResponse, ResetPasswordViewResponse, TwoFactorChallengeViewResponse, VerifyEmailViewResponse
{
    /**
     * The name of the view or the callable used to generate the view.
     */
    protected $view;

    /**
     * Create a new response instance.
     */
    public function __construct(
        callable|string $view,
        protected array $data = []
    ) {
        $this->view = $view;
    }

    /**
     * Create an HTTP response that represents the object.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function toResponse($request)
    {
        if (! is_callable($this->view) || is_string($this->view)) {
            return view($this->view, ['request' => $request] + $this->data);
        }

        $response = call_user_func(
            $this->view,
            ...array_values([...$this->data, 'request' => $request]),
        );

        if ($response instanceof Responsable) {
            return $response->toResponse($request);
        }

        return $response;
    }
}
