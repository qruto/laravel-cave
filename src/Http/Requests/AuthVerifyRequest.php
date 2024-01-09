<?php

namespace Qruto\Cave\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Qruto\Cave\Authenticators\AssertionCeremony;
use Qruto\Cave\Authenticators\AttestationCeremony;
use Qruto\Cave\Ceremony;

class AuthVerifyRequest extends FormRequest
{
    protected AssertionCeremony $assertion;

    protected AttestationCeremony $attestation;

    protected ?Ceremony $ceremony;

    public function __construct(...$args)
    {
        parent::__construct(...$args);

        $this->assertion = app(AssertionCeremony::class);
        $this->attestation = app(AttestationCeremony::class);

        if (session()->has($this->assertion::OPTIONS_SESSION_KEY)) {
            $this->ceremony = Ceremony::Assertion;
        }

        if (session()->has($this->attestation::OPTIONS_SESSION_KEY)) {
            $this->ceremony = Ceremony::Attestation;
        }
    }

    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        if (! $this->ceremony) {
            return false;
        }

        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return match ($this->ceremony) {
            Ceremony::Assertion => [
                'id' => ['required', 'string'],
                'type' => ['required', 'string', 'in:public-key'],
                'rawId' => ['required', 'string'],
                'response.authenticatorData' => ['required', 'string'],
                'response.clientDataJSON' => ['required', 'string'],
                'response.signature' => ['required', 'string'],
                'response.userHandle' => ['sometimes', 'nullable'],
                'remember' => ['nullable', 'string'],
            ],
            Ceremony::Attestation => [
                'id' => ['required', 'string'],
                'name' => ['nullable', 'string'],
                'type' => ['required', 'string', 'in:public-key'],
                'rawId' => ['required', 'string'],
                'response.clientDataJSON' => ['required', 'string'],
                'response.attestationObject' => ['required', 'string'],
            ],
        };
    }

    public function ceremony()
    {
        return $this->ceremony;
    }
}
