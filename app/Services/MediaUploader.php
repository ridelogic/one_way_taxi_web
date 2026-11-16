<?php

namespace App\Services;

use App\Models\MediaAsset;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * Stores uploaded images on the "uploads" disk with unique filenames and records their metadata.
 */
class MediaUploader
{
    public const ALLOWED_EXTENSIONS = ['jpg', 'jpeg', 'png', 'webp', 'gif', 'ico'];

    public const MAX_KILOBYTES = 5120;

    /** @param array<string, mixed> $attributes */
    public function store(UploadedFile $file, array $attributes = [], ?int $userId = null): MediaAsset
    {
        $path = $this->storeFile($file);
        [$width, $height] = $this->dimensions($file);

        return MediaAsset::create(array_merge([
            'disk' => 'uploads',
            'path' => $path,
            'original_name' => Str::limit($file->getClientOriginalName(), 250, ''),
            'mime_type' => $file->getMimeType(),
            'size_bytes' => $file->getSize(),
            'width' => $width,
            'height' => $height,
            'title' => Str::of(pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME))->replace(['-', '_'], ' ')->title()->limit(120, '')->toString(),
            'uploaded_by' => $userId,
        ], array_filter($attributes, fn ($v) => $v !== null)));
    }

    /** Replace the file behind an existing asset, keeping its ID (and therefore every place it is used). */
    public function replace(MediaAsset $media, UploadedFile $file): MediaAsset
    {
        $oldDisk = $media->disk;
        $oldPath = $media->path;
        $path = $this->storeFile($file);
        [$width, $height] = $this->dimensions($file);

        $media->update([
            'disk' => 'uploads',
            'path' => $path,
            'original_name' => Str::limit($file->getClientOriginalName(), 250, ''),
            'mime_type' => $file->getMimeType(),
            'size_bytes' => $file->getSize(),
            'width' => $width,
            'height' => $height,
        ]);

        if ($oldDisk === 'uploads' && $oldPath !== $path) {
            Storage::disk('uploads')->delete($oldPath);
        }

        return $media;
    }

    private function storeFile(UploadedFile $file): string
    {
        $extension = strtolower($file->guessExtension() ?: $file->getClientOriginalExtension());
        $extension = $extension === 'jpeg' ? 'jpg' : $extension;
        $slug = Str::slug(Str::limit(pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME), 40, '')) ?: 'image';
        $filename = $slug.'-'.Str::lower(Str::random(10)).'.'.$extension;

        return $file->storeAs('media/'.now()->format('Y/m'), $filename, 'uploads');
    }

    /** @return array{0: ?int, 1: ?int} */
    private function dimensions(UploadedFile $file): array
    {
        $size = @getimagesize($file->getRealPath());

        return $size ? [(int) $size[0], (int) $size[1]] : [null, null];
    }
}
