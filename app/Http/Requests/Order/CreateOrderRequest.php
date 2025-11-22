<?php

namespace App\Http\Requests\Order;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use App\Models\Artwork;

class CreateOrderRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user() && $this->user()->isBuyer();
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'artwork_id' => 'required|integer|exists:artworks,id',
            'payment_method' => 'required|string|in:mada,visa,mastercard,apple_pay,tamara,tabby',
            'buyer_name' => 'required|string|max:255',
            'buyer_phone' => [
                'required',
                'string',
                'max:20',
                function ($attribute, $value, $fail) {
                    if (!preg_match('/^(\+966|0)[5-9][0-9]{8}$/', $value)) {
                        $fail('رقم الهاتف يجب أن يكون رقم سعودي صالح.');
                    }
                },
            ],
            'shipping_address' => 'required|string|max:1000',
        ];
    }

    /**
     * Get the error messages for the defined validation rules.
     */
    public function messages(): array
    {
        return [
            'artwork_id.required' => 'رقم العمل الفني مطلوب.',
            'artwork_id.exists' => 'العمل الفني المحدد غير موجود.',
            'payment_method.required' => 'طريقة الدفع مطلوبة.',
            'payment_method.in' => 'طريقة الدفع المحددة غير صالحة.',
            'buyer_name.required' => 'اسم المشتري مطلوب.',
            'buyer_name.max' => 'اسم المشتري لا يجب أن يتجاوز 255 حرف.',
            'buyer_phone.required' => 'رقم الهاتف مطلوب.',
            'buyer_phone.regex' => 'رقم الهاتف يجب أن يكون رقم سعودي صالح.',
            'shipping_address.required' => 'عنوان الشحن مطلوب.',
            'shipping_address.max' => 'عنوان الشحن لا يجب أن يتجاوز 1000 حرف.',
        ];
    }

    /**
     * Configure the validator instance.
     */
    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            if ($this->artwork_id) {
                $artwork = Artwork::find($this->artwork_id);
                
                if ($artwork) {
                    // Check if artwork is available
                    if ($artwork->status !== Artwork::STATUS_AVAILABLE) {
                        $validator->errors()->add('artwork_id', 'العمل الفني غير متاح للشراء حالياً.');
                    }
                    
                    // Check if artist is approved
                    if ($artwork->artist->status !== \App\Models\Artist::STATUS_APPROVED) {
                        $validator->errors()->add('artwork_id', 'الفنان صاحب العمل غير معتمد حالياً.');
                    }
                    
                    // Store artwork in request for later use
                    $this->artwork = $artwork;
                }
            }
        });
    }

    /**
     * Get the artwork instance
     */
    public function getArtwork(): ?Artwork
    {
        return $this->artwork ?? null;
    }
}
