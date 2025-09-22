<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CandidateUploadRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['nullable', 'email', 'max:255'],
            'phone' => ['nullable', 'string', 'max:50'],
            'position_applied' => ['required', 'string', 'max:255'],
            'skills' => ['nullable', 'string'],
            'years_experience' => ['nullable', 'integer', 'min:0', 'max:60'],
            'education_level' => ['nullable', 'string', 'max:255'],
            'cv_file' => ['required', 'file', 'mimes:pdf,doc,docx', 'max:5120', 'min:1'],
        ];
    }


}
