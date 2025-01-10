<?php

namespace App\Http\Resources;

use App\Facades\FileManager;
use App\Services\StoreService;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class StoreResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $path = StoreService::UPLOAD_PATH . $this->id;

        return [
            'id' => $this->id,
            'name' => $this->name,
            'image' => FileManager::url($path, $this->image),
        ];
    }
}
