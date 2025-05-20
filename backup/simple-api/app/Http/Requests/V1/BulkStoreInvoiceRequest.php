<?php

namespace App\Http\Requests\V1;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
class BulkStoreInvoiceRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules()
    {
        return [
            '*.customerId' => ['required', 'integer'],
            '*.amount' => ['required', Rule::in(['B', 'P', 'V', 'b', 'p', 'v'])],
            '*.status' => ['required', 'email'],
            '*.billedDate' => ['required', 'date_format:y-m-d h:i:s'],
            '*.paidDate' => ['required', 'date_format:y-m-d', 'nullable'],
            'state' => ['required'],
            'postalCode' => ['required'],
        ];
    }

    protected function prepareForValidation() {
       $date = [];
       foreach ($this->toArray() as $obj) {
        $obj['customer_id'] = $obj['customerId'] ?? null;
        $obj['billed_date'] = $obj['billedDate'] ?? null;
        $obj['paid_date'] = $obj['paidDate'] ?? null;

        $data[] = $obj;

       }

       $this->merge($data);
    }
}
