<?php

namespace App\Http\Requests;

use App\Models\Listing;
use Illuminate\Foundation\Http\FormRequest;

class ListingRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // Ownership is enforced by ListingPolicy in the controller.
    }

    /** A blank price means "contact for price" and is stored as 0. */
    protected function prepareForValidation(): void
    {
        if ($this->input('price') === null || $this->input('price') === '') {
            $this->merge(['price' => 0]);
        }
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'min:3', 'max:120'],
            'description' => ['required', 'string', 'min:10', 'max:5000'],
            'category_id' => ['required', 'integer', 'exists:categories,id'],
            'location_id' => ['nullable', 'integer', 'exists:locations,id'],
            'price' => ['required', 'numeric', 'min:0', 'max:999999999'], // 0 = "contact for price"
            'is_negotiable' => ['boolean'],
            'condition' => ['required', 'in:'.implode(',', Listing::CONDITIONS)],
            'quantity' => ['nullable', 'integer', 'min:1', 'max:10000'],
            'images' => ['array', 'max:'.Listing::MAX_PHOTOS],
            'images.*' => ['image', 'mimes:jpeg,jpg,png,webp', 'max:2048'],
            'remove_image_ids' => ['array'],
            'remove_image_ids.*' => ['integer'],
            // Photos to show on the home feed, in order (the first is the cover): "e:<existing id>" or "n:<index of a new upload>".
            'feed' => ['array', 'max:'.Listing::MAX_FEED_PHOTOS],
            'feed.*' => ['string', 'regex:/^[en]:\d+$/'],
            'save_as_draft' => ['boolean'],
        ];
    }
}
