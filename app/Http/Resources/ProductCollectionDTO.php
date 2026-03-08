<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\ResourceCollection;

class ProductCollectionDTO extends ResourceCollection
{
    public function toArray($request)
    {
        return [
            'items' => ProductListDTO::collection($this->collection),
            'pagination' => [
                'currentPage' => $this->currentPage(),
                'lastPage' => $this->lastPage(),
                'perPage' => $this->perPage(),
                'total' => $this->total(),
                'hasMore' => $this->hasMorePages(),
            ]
        ];
    }
}