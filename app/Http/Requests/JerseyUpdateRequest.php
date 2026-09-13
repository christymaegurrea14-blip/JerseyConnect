<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class JerseyUpdateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name'             => ['required', 'string', 'max:255'],
            'description'      => ['nullable', 'string', 'max:2000'],
            'price'            => ['required', 'numeric', 'min:0'],
            'badge'            => ['nullable', 'in:New,Bestseller,Hot'],
            'primary_color'    => ['required', 'regex:/^#[0-9A-Fa-f]{6}$/'],
            'secondary_color'  => ['required', 'regex:/^#[0-9A-Fa-f]{6}$/'],
            'accent_color'     => ['required', 'regex:/^#[0-9A-Fa-f]{6}$/'],
            'sport'            => ['required', 'in:Basketball,Soccer,Baseball,Volleyball,Esports'],
            'status'           => ['required', 'in:active,inactive'],
            'image'            => ['nullable', 'image', 'max:4096'],
        ];
    }
}