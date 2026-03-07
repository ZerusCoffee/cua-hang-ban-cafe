<?php
namespace App\Http\Resources;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
class ProductDTO extends JsonResource
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
            'description' => $this->description,
            'isFeatured' => $this->is_featured,
            'isActive' => $this->is_active,
            'viewCount' => $this->view_count,
            'primaryImage' => new ProductImageDTO($this->whenLoaded('primaryImage')),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
