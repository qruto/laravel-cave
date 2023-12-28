<?php

namespace Qruto\Cave\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class AttestationRequest extends FormRequest
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
     *
     * @return array
     */
    public function rules()
    {
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
