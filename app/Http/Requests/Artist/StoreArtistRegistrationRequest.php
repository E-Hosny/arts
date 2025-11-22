<?php

namespace App\Http\Requests\Artist;

use Illuminate\Foundation\Http\FormRequest;

class StoreArtistRegistrationRequest extends FormRequest
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
            'email' => 'required|string|email|max:320|unique:users,email',
            'password' => 'required|string|min:8',
            'name' => 'required|string|max:255',
            'phone' => 'required|string|max:20',
            'city' => 'required|string|max:100',
            'bio' => 'required|string|max:500',
            'samples' => 'required|array|min:3|max:5',
            'samples.*.title' => 'required|string|max:255',
            'samples.*.description' => 'nullable|string|max:1000',
            'samples.*.image' => 'required|string', // Base64 string validation
        ];
    }

    /**
     * Configure the validator instance.
     *
     * @param  \Illuminate\Validation\Validator  $validator
     * @return void
     */
    public function withValidator($validator)
    {
        $validator->after(function ($validator) {
            // Validate base64 images
            if ($this->has('samples')) {
                foreach ($this->samples as $index => $sample) {
                    if (isset($sample['image'])) {
                        if (!$this->isValidBase64Image($sample['image'])) {
                            $validator->errors()->add(
                                "samples.{$index}.image",
                                'الصورة يجب أن تكون بتنسيق base64 صالح (JPEG, PNG, GIF)'
                            );
                        }

                        // Check image size (max 5MB)
                        $imageSize = $this->getBase64ImageSize($sample['image']);
                        if ($imageSize > 5 * 1024 * 1024) { // 5MB in bytes
                            $validator->errors()->add(
                                "samples.{$index}.image",
                                'حجم الصورة يجب ألا يتجاوز 5 ميجابايت'
                            );
                        }
                    }
                }
            }
        });
    }

    /**
     * Get the error messages for the defined validation rules.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'email.required' => 'البريد الإلكتروني مطلوب',
            'email.string' => 'البريد الإلكتروني يجب أن يكون نص',
            'email.email' => 'البريد الإلكتروني غير صحيح',
            'email.max' => 'البريد الإلكتروني يجب ألا يتجاوز 320 حرف',
            'email.unique' => 'هذا البريد الإلكتروني مستخدم بالفعل',
            
            'password.required' => 'كلمة المرور مطلوبة',
            'password.string' => 'كلمة المرور يجب أن تكون نص',
            'password.min' => 'كلمة المرور يجب أن تكون 8 أحرف على الأقل',
            
            'name.required' => 'الاسم مطلوب',
            'name.string' => 'الاسم يجب أن يكون نص',
            'name.max' => 'الاسم يجب ألا يتجاوز 255 حرف',
            
            'phone.required' => 'رقم الهاتف مطلوب',
            'phone.string' => 'رقم الهاتف يجب أن يكون نص',
            'phone.max' => 'رقم الهاتف يجب ألا يتجاوز 20 حرف',
            
            'city.required' => 'المدينة مطلوبة',
            'city.string' => 'المدينة يجب أن تكون نص',
            'city.max' => 'المدينة يجب ألا تتجاوز 100 حرف',
            
            'bio.required' => 'السيرة الذاتية مطلوبة',
            'bio.string' => 'السيرة الذاتية يجب أن تكون نص',
            'bio.max' => 'السيرة الذاتية يجب ألا تتجاوز 500 حرف',
            
            'samples.required' => 'نماذج الأعمال مطلوبة',
            'samples.array' => 'نماذج الأعمال يجب أن تكون مصفوفة',
            'samples.min' => 'يجب تقديم 3 نماذج أعمال على الأقل',
            'samples.max' => 'لا يمكن تقديم أكثر من 5 نماذج أعمال',
            
            'samples.*.title.required' => 'عنوان العمل مطلوب',
            'samples.*.title.string' => 'عنوان العمل يجب أن يكون نص',
            'samples.*.title.max' => 'عنوان العمل يجب ألا يتجاوز 255 حرف',
            
            'samples.*.description.string' => 'وصف العمل يجب أن يكون نص',
            'samples.*.description.max' => 'وصف العمل يجب ألا يتجاوز 1000 حرف',
            
            'samples.*.image.required' => 'صورة العمل مطلوبة',
            'samples.*.image.string' => 'صورة العمل يجب أن تكون نص base64',
        ];
    }

    /**
     * Get custom attributes for validator errors.
     *
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'samples.*.title' => 'عنوان العمل',
            'samples.*.description' => 'وصف العمل',
            'samples.*.image' => 'صورة العمل',
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
            'message' => 'بيانات تسجيل الفنان غير صحيحة',
            'errors' => $validator->errors(),
        ], 422);

        throw new \Illuminate\Validation\ValidationException($validator, $response);
    }

    /**
     * Check if the base64 string is a valid image
     */
    private function isValidBase64Image(string $base64String): bool
    {
        try {
            // Remove data URL prefix if exists
            $cleanBase64 = preg_replace('/^data:image\/\w+;base64,/', '', $base64String);
            
            // If empty after cleaning, it's invalid
            if (empty($cleanBase64)) {
                return false;
            }
            
            // Check if valid base64 format
            if (!preg_match('/^[a-zA-Z0-9\/\r\n+]*={0,2}$/', $cleanBase64)) {
                return false;
            }
            
            // Decode base64
            $decoded = @base64_decode($cleanBase64, true);
            if ($decoded === false || empty($decoded)) {
                return false;
            }

            // Check if it's a valid image using getimagesizefromstring
            $imageInfo = @getimagesizefromstring($decoded);
            if ($imageInfo === false) {
                return false;
            }

            // Check allowed MIME types
            $allowedMimes = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif', 'image/webp'];
            $mimeType = $imageInfo['mime'] ?? '';
            
            return in_array(strtolower($mimeType), $allowedMimes);
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * Get the size of base64 encoded image in bytes
     */
    private function getBase64ImageSize(string $base64String): int
    {
        try {
            // Remove data URL prefix if exists
            $cleanBase64 = preg_replace('/^data:image\/\w+;base64,/', '', $base64String);
            
            // Calculate size: (base64_length * 3) / 4
            return (int) ((strlen($cleanBase64) * 3) / 4);
        } catch (\Exception $e) {
            return 0;
        }
    }
}