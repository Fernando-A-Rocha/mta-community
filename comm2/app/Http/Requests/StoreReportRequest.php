<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Models\Report;
use App\Rules\EnglishOnly;
use App\Rules\NoHtml;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreReportRequest extends FormRequest
{
    protected $errorBag = 'report';

    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    protected function prepareForValidation(): void
    {
        if ($this->has('report_reason')) {
            $this->merge(['reason' => $this->input('report_reason')]);
        }

        if ($this->has('report_comment')) {
            $this->merge(['comment' => $this->input('report_comment')]);
        }
    }

    /**
     * @return array<string, list<ValidationRule|mixed>|string>
     */
    public function rules(): array
    {
        $reportableType = $this->reportableType();
        $reasonOptions = array_keys(Report::reasonOptionsFor($reportableType));

        return [
            'reason' => ['required', 'string', Rule::in($reasonOptions)],
            'comment' => [
                'required',
                'string',
                'min:'.Report::COMMENT_MIN_LENGTH,
                'max:2000',
                new NoHtml,
                new EnglishOnly,
            ],
        ];
    }

    public function reportableType(): string
    {
        return $this->routeIs('reports.resources.store') ? Report::TYPE_RESOURCE : Report::TYPE_USER;
    }
}
