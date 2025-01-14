<?php

namespace App\Http\Resources;

use App\Facades\FileManager;
use Illuminate\Http\Request;
use App\Http\Controllers\UserController;
use Illuminate\Http\Resources\Json\JsonResource;

class UserResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $path = UserController::UPLOAD_PATH . $this->id . '/';
        return [
            'first_name'=>$this->first_name,
            'last_name'=>$this->last_name,
            'phone'=>$this->phone,
            'photo'=>FileManager::url($path, $this->photo),

        ];
}
}
