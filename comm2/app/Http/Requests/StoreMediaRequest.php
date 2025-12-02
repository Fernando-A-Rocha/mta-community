<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Rules\NoHtml;
use App\Services\MediaUploadService;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreMediaRequest extends FormRequest
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
            'type' => [
                'required',
                'string',
                Rule::in(['image', 'video']),
            ],
            'images' => [
                'required_if:type,image',
                'array',
                'max:5',
            ],
            'images.*' => [
                'required',
                'image',
                'mimes:jpg,jpeg,png,webp',
                'max:1024', // 1MB in KB
                'dimensions:max_width=3840,max_height=2160', // Allow up to 4K, will be auto-resized to 1080p
            ],
            'youtube_url' => [
                'required_if:type,video',
                'nullable',
                'url',
                function ($attribute, $value, $fail) {
                    if ($this->input('type') === 'video' && $value) {
                        $uploadService = app(MediaUploadService::class);
                        if (! $uploadService->isValidYouTubeUrl($value)) {
                            $fail('The :attribute must be a valid YouTube URL.');
                        }
                    }
                },
            ],
            'description' => [
                'required',
                'string',
                'max:100',
                new NoHtml,
            ],
        ];
    }

    /**
     * Get custom messages for validator errors.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'type.required' => 'Please select a media type (image or video).',
            'type.in' => 'Media type must be either image or video.',
            'images.required_if' => 'At least one image is required for image type media.',
            'images.max' => 'Maximum 5 images allowed.',
            'images.*.image' => 'Each file must be an image.',
            'images.*.mimes' => 'Images must be in JPEG, PNG, or WebP format.',
            'images.*.max' => 'Each image must not exceed 1MB.',
            'images.*.dimensions' => 'Each image must not exceed 3840x2160 pixels (4K). Images will be automatically resized to 1080p if larger.',
            'youtube_url.required_if' => 'YouTube URL is required for video type media.',
            'youtube_url.url' => 'Please provide a valid URL.',
            'description.required' => 'A description is required.',
            'description.max' => 'Description must not exceed 100 characters.',
        ];
    }
}
