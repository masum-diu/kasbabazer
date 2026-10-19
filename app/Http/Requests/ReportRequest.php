<?php

namespace App\Http\Requests;

use App\Models\Report;
use Illuminate\Foundation\Http\FormRequest;

class ReportRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'type' => ['required', 'in:listing,comment,user'],
            'id' => ['required', 'integer'],
            'reason' => ['required', 'in:'.implode(',', Report::REASONS)],
            'description' => ['nullable', 'string', 'max:1000'],
        ];
    }
}
