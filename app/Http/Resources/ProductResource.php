<?php

namespace App\Http\Resources;

use App\Facades\FileManager;
use App\Services\ProductService;
use App\Services\StoreService;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ProductResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $path = StoreService::UPLOAD_PATH . $this->store->id . ProductService::UPLOAD_PATH;

        return [
            'id' => $this->id,
            'name' => $this->name,
            'description' => $this->description,
            'price' => $this->price,
            'discount' => $this->offer->discount,
            'image' => FileManager::url($path, $this->image),
            'category' => new CategoryResource($this->category),
            'rate' => $this->rate,
            'is_favorite' => $this->favorite,
            'quantity' => $this->quantity,
            'store' => new StoreResource($this->store),
        ];
    }
}
