<?php

declare(strict_types=1);

namespace App\Modules\License\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ProvisionLicenseRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true; // Authorization handled by middleware
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, array<int, string>>
     */
    public function rules(): array
    {
        return [
            'customer_email'               => ['required', 'email', 'max:255'],
            'products'                     => ['required', 'array', 'min:1'],
            'products.*.product_public_id' => ['required', 'string', 'exists:products,public_id'],
            'products.*.expires_at'        => ['nullable', 'date', 'after:today'],
            'products.*.max_activations'   => ['nullable', 'integer', 'min:1'],
            'license_key'                  => ['nullable', 'string', 'size:19'], // Format: XXXX-XXXX-XXXX-XXXX
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
            'customer_email.required'          => 'Customer email is required',
            'customer_email.email'             => 'Customer email must be a valid email address',
            'products.required'                => 'At least one product is required',
            'products.min'                     => 'At least one product is required',
            'products.*.product_slug.required' => 'Product slug is required for each product',
            'products.*.expires_at.after'      => 'Expiration date must be in the future',
            'license_key.size'                 => 'License key must be 19 characters long',
        ];
    }
}
