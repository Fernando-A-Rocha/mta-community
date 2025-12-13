<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Rules\EnglishOnly;
use App\Rules\GitHubUrl;
use App\Rules\MtaForumUrl;
use App\Rules\NoHtml;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreNewResourceRequest extends FormRequest
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
            'long_description' => [
                'required',
                'string',
                'min:50',
                'max:10000',
                new NoHtml,
                new EnglishOnly,
            ],
            'languages' => [
                'nullable',
                'array',
            ],
            'languages.*' => [
                'required',
                'integer',
                Rule::exists('languages', 'id'),
            ],
            'tags' => [
                'nullable',
                'array',
                'max:5',
            ],
            'tags.*' => [
                'required',
                'integer',
                Rule::exists('tags', 'id'),
            ],
            'images' => [
                'nullable',
                'array',
                'max:10',
            ],
            'images.*' => [
                'required',
                'image',
                'mimes:jpg,jpeg,png,webp',
                'max:2048', // 2MB per image - See docs/UPLOAD_LIMITS.md for nginx/PHP alignment
            ],
            'github_url' => [
                'nullable',
                'url',
                'max:500',
                new GitHubUrl,
            ],
            'forum_thread_url' => [
                'nullable',
                'url',
                'max:500',
                new MtaForumUrl,
            ],
        ];
    }
}
