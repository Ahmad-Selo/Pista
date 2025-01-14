<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class OrderResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'price'=>$this->price,
            'state'=>$this->state,
            'delivery_time'=>$this->delivery_time,
            'created_at'=>$this->created_at,
            'address'=>new AddressResource($this->address),
            'products'=> $this->products,
        ];
    }
}
