<?php

namespace Qruto\Cave\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class AuthAssertionVerifyRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            'id' => ['required', 'string'],
            'type' => ['required', 'string', 'in:public-key'],
            'rawId' => ['required', 'string'],
            'response.authenticatorData' => ['required', 'string'],
            'response.clientDataJSON' => ['required', 'string'],
            'response.signature' => ['required', 'string'],
            'response.userHandle' => ['sometimes', 'nullable'],
            'remember' => ['nullable', 'string'],
        ];
    }
}
