<?php

namespace Qruto\Cave\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Qruto\Cave\Cave;

class LoginRequest extends FormRequest
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
            Cave::username() => 'required|string',
            'password' => 'required|string',
        ];
    }
}
