<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class TransactionMessageRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'body'  => ['required', 'string', 'max:400'],
            'image_path' => ['nullable', 'mimes:jpeg,png', 'max:5120'],
        ];
    }

    public function messages(): array
    {
        return [
            'body.required' => '本文を入力してください',
            'body.string' => '本文は文字列で入力してください',
            'body.max'      => '本文は400文字以内で入力してください',
            'image_path.mimes'   => '「.png」または「.jpeg」形式でアップロードしてください',
            'image_path.max'      => '画像サイズは5MB以下にしてください。',
        ];
    }
}
