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

    protected Ceremony $type;

    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        $this->assertion = app(AssertionCeremony::class);
        $this->attestation = app(AttestationCeremony::class);

        if ($this->session()->has($this->assertion::OPTIONS_SESSION_KEY)) {
            $this->type = Ceremony::Assertion;

            return true;
        }

        if ($this->session()->has($this->attestation::OPTIONS_SESSION_KEY)) {
            $this->type = Ceremony::Attestation;

            return true;
        }

        return false;
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        switch ($this->type) {
            case Ceremony::Assertion:
                return [
                    'id' => ['required', 'string'],
                    'type' => ['required', 'string'],
                    'rawId' => ['required', 'string'],
                    'response.authenticatorData' => ['required', 'string'],
                    'response.clientDataJSON' => ['required', 'string'],
                    'response.signature' => ['required', 'string'],
                    'response.userHandle' => ['sometimes', 'nullable'],
                    'remember' => ['nullable', 'string'],
                ];
            case Ceremony::Attestation:
                return [
                    'id' => ['required', 'string'],
                    'name' => ['required', 'string'],
                    'type' => ['required', 'string'],
                    'rawId' => ['required', 'string'],
                    'response.clientDataJSON' => ['required', 'string'],
                    'response.attestationObject' => ['required', 'string'],
                ];
        }
    }
}
