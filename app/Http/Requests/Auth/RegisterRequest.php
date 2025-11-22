<?php

namespace App\Http\Requests\Auth;

use Illuminate\Foundation\Http\FormRequest;

class RegisterRequest extends FormRequest
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
    public function rules(): array
    {
        return [
            'name' => 'required|string|max:255',
            'email' => 'required|string|email:rfc,dns|max:320|unique:users,email',
            'password' => 'required|string|min:8|confirmed',
            'avatar_url' => 'nullable|url|max:2048',
        ];
    }

    /**
     * Get the error messages for the defined validation rules.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'name.required' => 'اسم المستخدم مطلوب',
            'name.string' => 'اسم المستخدم يجب أن يكون نص',
            'name.max' => 'اسم المستخدم يجب ألا يتجاوز 255 حرف',
            
            'email.required' => 'البريد الإلكتروني مطلوب',
            'email.string' => 'البريد الإلكتروني يجب أن يكون نص',
            'email.email' => 'البريد الإلكتروني غير صحيح',
            'email.max' => 'البريد الإلكتروني يجب ألا يتجاوز 320 حرف',
            'email.unique' => 'هذا البريد الإلكتروني مستخدم بالفعل',
            
            'password.required' => 'كلمة المرور مطلوبة',
            'password.string' => 'كلمة المرور يجب أن تكون نص',
            'password.min' => 'كلمة المرور يجب أن تكون 8 أحرف على الأقل',
            'password.confirmed' => 'تأكيد كلمة المرور غير مطابق',
            
            'avatar_url.url' => 'رابط الصورة الشخصية غير صحيح',
            'avatar_url.max' => 'رابط الصورة الشخصية يجب ألا يتجاوز 2048 حرف',
        ];
    }

    /**
     * Handle a failed validation attempt.
     *
     * @param  \Illuminate\Contracts\Validation\Validator  $validator
     * @return void
     *
     * @throws \Illuminate\Validation\ValidationException
     */
    protected function failedValidation(\Illuminate\Contracts\Validation\Validator $validator)
    {
        $response = response()->json([
            'success' => false,
            'message' => 'بيانات التسجيل غير صحيحة',
            'errors' => $validator->errors(),
        ], 422);

        throw new \Illuminate\Validation\ValidationException($validator, $response);
    }
}