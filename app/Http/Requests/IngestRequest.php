<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * class ingestrequest
 *
 * handles validation for metric ingestion requests.
 *
 * this request validates incoming metric data before it is processed
 * by the application. authorization is handled separately through
 * api key middleware, so all requests reaching this class are allowed.
 *
 * expected payload example:
 *
 * {
 *     "metric": "cpu_usage",
 *     "value": 85.5,
 *     "ts": 1718123456,
 *     "source": "server-1"
 * }
 */
class IngestRequest extends FormRequest
{
    /**
     * determine whether the user is authorized to make this request.
     *
     * authorization is delegated to the api key middleware, therefore
     * this request always returns true.
     *
     * @return bool
     */
    public function authorize(): bool
    {
        return true; // we already authorized via the api key middleware
    }

    /**
     * get the validation rules that apply to the ingestion request.
     *
     * validation rules:
     *
     * - metric (required)
     *   - must be a string.
     *   - maximum length: 100 characters.
     *   - allowed characters:
     *     letters, numbers, periods (.), underscores (_), and hyphens (-).
     *   - examples:
     *     - cpu_usage
     *     - sales.total
     *     - api-requests
     *
     * - value (required)
     *   - must be numeric.
     *   - supports integers and decimal values.
     *
     * - ts (optional)
     *   - unix timestamp.
     *   - must be a non-negative integer.
     *   - if omitted, the server will generate a timestamp.
     *
     * - source (optional)
     *   - identifier describing where the metric originated.
     *   - maximum length: 80 characters.
     *   - examples:
     *     - server-1
     *     - web-app
     *     - payment-service
     *
     * @return array<string, array<int, string>>
     */
    public function rules(): array
    {
        return [
            /**
             * metric name (e.g. cpu_usage, sales.total)
             *
             * required:
             * - string
             * - max length of 100 characters
             * - allows letters, numbers, periods, underscores, and hyphens
             */
            'metric' => [
                'required',
                'string',
                'max:100',
                'regex:/^[a-z0-9._-]+$/i',
            ],

            /**
             * metric value
             *
             * required:
             * - numeric value (integer or decimal)
             */
            'value' => [
                'required',
                'numeric',
            ],

            /**
             * optional unix timestamp
             *
             * if omitted, the server generates the timestamp automatically.
             */
            'ts' => [
                'sometimes',
                'integer',
                'min:0',
            ],

            /**
             * optional source identifier
             *
             * examples:
             * - server-1
             * - web-app
             * - payment-service
             */
            'source' => [
                'sometimes',
                'string',
                'max:80',
            ],
        ];
    }
}
