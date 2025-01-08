<?php

namespace App\Services;

use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class FileService
{
    public function getContent(string $path, string $filename = null)
    {
        if ($filename != null) {
            $path = Str::of($path)->finish('/');
        }

        return Storage::disk('public')->get($path . $filename);
    }

    public function store(string $path, $file, string $filename = null)
    {
        $path = Str::of($path)->finish('/');

        if ($filename == null) {
            $filename = $file->getClientOriginalName();
        } else {
            $filename .= '.' . $file->getClientOriginalExtension();
        }

        $file->storeAs($path, $filename, 'public');
        return $filename;
    }

    public function url(string $path, string $filename = null)
    {
        if ($filename != null) {
            $path = Str::of($path)->finish('/');
        }

        return url(Storage::url($path . $filename));
    }

    public function rename(string $path, string $oldFilename, string $newFilename)
    {
        $path = Str::of($path)->finish('/');

        $content = $this->getContent($path, $oldFilename);
        $extension = Str::afterLast($oldFilename, '.');

        $newFilename .= '.' . $extension;

        Storage::disk('public')->put($path . $newFilename, $content);

        $this->delete($path, $oldFilename);

        return $newFilename;
    }

    public function delete(string $path, string $filename = null)
    {
        if ($filename != null) {
            $path = Str::of($path)->finish('/');

            return Storage::disk('public')->delete($path . $filename);
        }

        return Storage::disk('public')->deleteDirectory($path);
    }
}