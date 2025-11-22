<?php

namespace App\Http\Requests\Artwork;

use Illuminate\Foundation\Http\FormRequest;

class StoreArtworkRequest extends FormRequest
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
            'title' => 'required|string|max:255',
            'description' => 'required|string|max:2000',
            'price' => 'required|integer|min:500|max:10000',
            'category' => 'required|string|in:painting,sculpture,photography,digital_art,traditional_art,calligraphy,mixed_media',
            'dimensions' => 'nullable|string|max:100',
            'materials' => 'nullable|string|max:255',
            'images' => 'required|array|min:1|max:5',
            'images.*' => 'required|string', // Base64 image validation
        ];
    }

    /**
     * Configure the validator instance.
     */
    public function withValidator($validator)
    {
        $validator->after(function ($validator) {
            // Validate base64 images
            if ($this->has('images')) {
                foreach ($this->images as $index => $image) {
                    if (!$this->isValidBase64Image($image)) {
                        $validator->errors()->add(
                            "images.{$index}",
                            'الصورة يجب أن تكون بتنسيق base64 صالح (JPEG, PNG, GIF, WebP)'
                        );
                    }

                    // Check image size (max 10MB per image)
                    $imageSize = $this->getBase64ImageSize($image);
                    if ($imageSize > 10 * 1024 * 1024) { // 10MB in bytes
                        $validator->errors()->add(
                            "images.{$index}",
                            'حجم الصورة يجب ألا يتجاوز 10 ميجابايت'
                        );
                    }
                }
            }
        });
    }

    /**
     * Get the error messages for the defined validation rules.
     */
    public function messages(): array
    {
        return [
            'title.required' => 'عنوان العمل الفني مطلوب',
            'title.string' => 'عنوان العمل الفني يجب أن يكون نص',
            'title.max' => 'عنوان العمل الفني يجب ألا يتجاوز 255 حرف',
            
            'description.required' => 'وصف العمل الفني مطلوب',
            'description.string' => 'وصف العمل الفني يجب أن يكون نص',
            'description.max' => 'وصف العمل الفني يجب ألا يتجاوز 2000 حرف',
            
            'price.required' => 'سعر العمل الفني مطلوب',
            'price.integer' => 'سعر العمل الفني يجب أن يكون رقم صحيح',
            'price.min' => 'الحد الأدنى لسعر العمل الفني هو 500 ريال',
            'price.max' => 'الحد الأقصى لسعر العمل الفني هو 10,000 ريال',
            
            'category.required' => 'فئة العمل الفني مطلوبة',
            'category.string' => 'فئة العمل الفني يجب أن تكون نص',
            'category.in' => 'فئة العمل الفني غير صحيحة',
            
            'dimensions.string' => 'أبعاد العمل يجب أن تكون نص',
            'dimensions.max' => 'أبعاد العمل يجب ألا تتجاوز 100 حرف',
            
            'materials.string' => 'المواد المستخدمة يجب أن تكون نص',
            'materials.max' => 'المواد المستخدمة يجب ألا تتجاوز 255 حرف',
            
            'images.required' => 'صور العمل الفني مطلوبة',
            'images.array' => 'صور العمل الفني يجب أن تكون مصفوفة',
            'images.min' => 'يجب تقديم صورة واحدة على الأقل',
            'images.max' => 'لا يمكن تقديم أكثر من 5 صور',
            
            'images.*.required' => 'صورة العمل مطلوبة',
            'images.*.string' => 'صورة العمل يجب أن تكون نص base64',
        ];
    }

    /**
     * Handle a failed validation attempt.
     */
    protected function failedValidation(\Illuminate\Contracts\Validation\Validator $validator)
    {
        $response = response()->json([
            'success' => false,
            'message' => 'بيانات العمل الفني غير صحيحة',
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

            // Check if it's a valid image
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
            $cleanBase64 = preg_replace('/^data:image\/\w+;base64,/', '', $base64String);
            return (int) ((strlen($cleanBase64) * 3) / 4);
        } catch (\Exception $e) {
            return 0;
        }
    }
}