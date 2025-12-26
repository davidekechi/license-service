<?php

declare(strict_types=1);

namespace App\Modules\License\Requests;

use App\Modules\License\Enums\InstanceType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ActivateLicenseRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true; // No authentication required for activation
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            'instance_identifier' => ['required', 'string', 'max:255'],
            'instance_type'       => ['required', 'string', Rule::in(InstanceType::values())],
            'product_slug'        => ['required', 'string'],
            'instance_meta'       => ['nullable', 'array'],
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
            'instance_identifier.required' => 'Instance identifier is required',
            'instance_type.required'       => 'Instance type is required',
            'instance_type.in'             => 'Instance type must be one of: site, device, server',
            'product_slug.required'        => 'Product slug is required',
        ];
    }
}
