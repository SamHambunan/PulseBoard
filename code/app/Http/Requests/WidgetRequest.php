<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class WidgetRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // We use Policies in the controller
    }

    public function rules(): array
    {
        return [
            'type' => ['required', 'string', 'in:line_chart,bar_chart,gauge,stat'],
            'config' => ['required', 'array'],
            'config.metric' => ['required', 'string'], // Ensure every widget points to a specific metric
            'position_x' => ['required', 'integer', 'min:0'],
            'position_y' => ['required', 'integer', 'min:0'],
            'width' => ['required', 'integer', 'min:1', 'max:12'], // Grid system limits
            'height' => ['required', 'integer', 'min:1'],
        ];
    }
}
