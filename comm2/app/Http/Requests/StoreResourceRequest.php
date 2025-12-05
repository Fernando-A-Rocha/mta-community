<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Rules\EnglishOnly;
use App\Rules\GitHubUrl;
use App\Rules\MtaForumUrl;
use App\Rules\NoHtml;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreResourceRequest extends FormRequest
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
        $isFirstVersion = $this->input('upload_mode') === 'first_version';

        $rules = [
            'zip_file' => [
                'required',
                'file',
                'mimes:zip',
                'max:20480', // 20MB in KB - See docs/UPLOAD_LIMITS.md for nginx/PHP alignment
            ],
            'upload_mode' => [
                'required',
                'in:first_version,new_release',
            ],
        ];

        if ($isFirstVersion) {
            // First version: require long_description, allow tags and images
            $rules['long_description'] = [
                'required',
                'string',
                'min:50',
                'max:10000',
                new NoHtml,
                new EnglishOnly,
            ];
            $rules['changelog'] = [
                'nullable',
                'string',
                'max:5000',
                new NoHtml,
                new EnglishOnly,
            ];
            $rules['languages'] = [
                'nullable',
                'array',
            ];
            $rules['languages.*'] = [
                'required',
                'integer',
                Rule::exists('languages', 'id'),
            ];
            $rules['tags'] = [
                'nullable',
                'array',
                'max:5',
            ];
            $rules['tags.*'] = [
                'required',
                'integer',
                Rule::exists('tags', 'id'),
            ];
            $rules['images'] = [
                'nullable',
                'array',
                'max:10',
            ];
            $rules['images.*'] = [
                'required',
                'image',
                'mimes:jpg,jpeg,png,webp',
                'max:2048', // 2MB per image - See docs/UPLOAD_LIMITS.md for nginx/PHP alignment
            ];
            $rules['github_url'] = [
                'nullable',
                'url',
                'max:500',
                new GitHubUrl,
            ];
            $rules['forum_thread_url'] = [
                'nullable',
                'url',
                'max:500',
                new MtaForumUrl,
            ];
        } else {
            // New release: require changelog, disallow tags and images
            $rules['changelog'] = [
                'required',
                'string',
                'min:10',
                'max:5000',
                new NoHtml,
                new EnglishOnly,
            ];
            $rules['long_description'] = [
                'nullable',
                'string',
                'max:10000',
                new NoHtml,
                new EnglishOnly,
            ];
        }

        return $rules;
    }
}
