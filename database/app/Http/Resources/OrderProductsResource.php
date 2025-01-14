<?php

namespace App\Http\Resources;

use App\Facades\FileManager;
use App\Services\ProductService;
use App\Services\StoreService;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class OrderProductsResource extends JsonResource
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
            'name'=>$this->name,
            'description'=>$this->description,
            'price'=>$this->price,
            'image'=>FileManager::url($path, $this->image),
            'quantity'=>$this->quantity,
            'rate'=>$this->rate,
            'selected quantity'=>$this->pivotQuantity,
            'store'=> $this->store
        ];
    }
}
