<?php
namespace App\Http\Resources;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
class ProductListDTO extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'category' => new CategoryDTO($this->whenLoaded('category')),
            'name' => $this->name,
            'slug' => $this->slug,
            'sku' => $this->sku,
            'price' => $this->recommended_price,
            'shortDescription' => $this->short_description,
            'viewCount' => $this->view_count,
            'primaryImage' => $this->primaryImage
                ? asset('storage/' . $this->primaryImage->image_path)
                : null,
            'inStock' => (bool) ($this->in_stock ?? true),
        ];
    }
}
