<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class BlockRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * `modules` — blokka biriktiriladigan modul id'lari. Massivdagi tartib
     * talaba uchun majburiy yechish ketma-ketligini belgilaydi.
     */
    public function rules(): array
    {
        $blockId = $this->route('block')?->id;

        return [
            'name' => 'required|string|max:255',
            'description' => 'nullable|string|max:10000',
            'is_active' => 'required|boolean',
            'modules' => 'required|array|min:1',
            'modules.*' => [
                'required',
                'integer',
                'distinct',
                'exists:modules,id',
                // Modul boshqa blokka biriktirilgan bo'lsa qayta tanlanmasin.
                Rule::unique('block_module', 'module_id')->where(
                    fn ($query) => $blockId ? $query->where('block_id', '!=', $blockId) : $query
                ),
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'modules.required' => 'Kamida bitta modul tanlang.',
            'modules.*.unique' => 'Tanlangan modul allaqachon boshqa blokka biriktirilgan.',
        ];
    }
}
