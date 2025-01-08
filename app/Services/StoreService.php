<?php

namespace App\Services;

use App\Enums\Role;
use App\Http\Requests\StoreCreateRequest;
use App\Http\Requests\StoreUpdateRequest;
use App\Models\Store;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\HttpException;

class StoreService
{
    public const UPLOAD_PATH = 'uploads/stores/';

    private FileService $fileService;

    public function __construct(FileService $fileService)
    {
        $this->fileService = $fileService;
    }

    private function ownership(Store $store, User $user): bool
    {
        return ($store->user->id == $user->id);
    }

    private function storeOwnerOrAdmin(Store $store, User $user): void
    {
        throw_if(
            !$this->ownership($store, $user) && !$user->hasRole(Role::ADMIN),
            AccessDeniedHttpException::class,
            'access denied',
        );
    }

    private function getDuration($longitude, $latitude)
    {
        $response = Http::get(
            config('services.map.osrm.route') . '36.2920484,33.4953687;' . $longitude . ',' . $latitude,
            ['overview' => 'false']
        );

        throw_if(
            $response->failed(),
            HttpException::class,
            "Failed to fetch data from the OSRM API. Status: {$response->status()}, Body: {$response->body()}"
        );

        return $response->json('routes')[0]['duration'];
    }

    private function getFileContent($store, string $filename = null)
    {
        if ($filename == null) {
            $filename = $store->image;
        }

        return $this->fileService->getContent(self::UPLOAD_PATH . $store->id, $filename);
    }

    private function storeFile($store, $file, string $filename = null)
    {
        return $this->fileService->store(self::UPLOAD_PATH . $store->id, $file, $filename);
    }

    private function fileUrl($store, string $filename = null)
    {
        if ($filename == null) {
            $filename = $store->image;
        }

        return $this->fileService->url(self::UPLOAD_PATH . $store->id, $filename);
    }

    private function renameFile($store, string $oldFilename, string $newFilename)
    {
        return $this->fileService->rename(self::UPLOAD_PATH . $store->id, $oldFilename, $newFilename);
    }

    private function deleteDirectoryOrFile($store, string $filename = null)
    {
        return $this->fileService->delete(self::UPLOAD_PATH . $store->id, $filename);
    }

    public function index()
    {
        $stores = Store::latest()->paginate(20);

        return $stores;
    }

    public function products(Store $store)
    {
        $products = $store->products()->paginate(20);

        return $products;
    }

    public function store(StoreCreateRequest $request)
    {
        $validated = $request->validated();

        $image = $request->file('image');

        $filename = $validated['store']['name'] . '_' . Str::uuid7();

        $validated['store']['image'] = $filename . '.' . $image->getClientOriginalExtension();

        $validated['warehouse']['retrieval_time'] = $this->getDuration(
            $validated['address']['longitude'],
            $validated['address']['latitude']
        );

        $store = Store::create($validated['store']);

        $store->warehouse()->create($validated['warehouse'])
            ->address()->create($validated['address']);

        $this->storeFile(
            $store,
            $image,
            $filename
        );

        return true;
    }

    public function show(Store $store)
    {
        $user = Auth::user();

        $this->storeOwnerOrAdmin($store, $user);

        $store->image = $this->fileUrl($store);

        return $store;
    }

    public function update(StoreUpdateRequest $request, Store $store)
    {
        $validated = $request->validated();

        if ($request->hasFile('image')) {
            $this->deleteDirectoryOrFile($store, $store->image);

            $filename = ($request->has('store_name') ? $validated['store']['name'] : $store->name) . '_' . Str::uuid7();

            $validated['store']['image'] = $this->storeFile(
                $store,
                $request->file('image'),
                $filename
            );
        }

        if ($request->has('store_name') && !$request->hasFile('image')) {
            $newFilename = $validated['store']['name'] . '_' . Str::uuid7();

            $validated['store']['image'] = $this->renameFile(
                $store,
                $store->image,
                $newFilename
            );
        }

        if ($request->has('address_name')) {
            $validated['address']['retrieval_time'] = $this->getDuration(
                $validated['address']['longitude'],
                $validated['address']['latitude']
            );
        }

        if (isset($validated['store'])) {
            $store->update($validated['store']);
        }
        if (isset($validated['warehouse'])) {
            $store->warehouse->update($validated['warehouse']);
        }
        if (isset($validated['address'])) {
            $store->warehouse->address->update($validated['address']);
        }

        return true;
    }

    public function destroy(Store $store)
    {
        $user = Auth::user();

        $this->storeOwnerOrAdmin($store, $user);

        $this->deleteDirectoryOrFile($store);

        return $store->delete();
    }

}