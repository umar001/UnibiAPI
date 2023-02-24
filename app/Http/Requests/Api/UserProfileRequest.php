<?php

namespace App\Http\Requests\Api;

use Illuminate\Foundation\Http\FormRequest;

class UserProfileRequest extends FormRequest
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
            'bio' => 'required',
            'interest' => 'required',
            'topics' => 'required',
            'language' => 'required',
            'gender' => 'required',
            'date_of_birth' => 'required',
            'first_name' => 'required',
            'last_name' => 'required',
        ];
    }
}
