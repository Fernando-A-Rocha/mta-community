<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Rules\EnglishOnly;
use App\Rules\NoHtml;
use Illuminate\Foundation\Http\FormRequest;

class StoreNewVersionRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'zip_file' => [
                'required',
                'file',
                'mimes:zip',
                'max:20480', // 20MB in KB - See docs/UPLOAD_LIMITS.md for nginx/PHP alignment
            ],
            'changelog' => [
                'required',
                'string',
                'min:10',
                'max:5000',
                new NoHtml,
                new EnglishOnly,
            ],
        ];
    }
}
