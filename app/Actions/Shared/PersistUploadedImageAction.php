<?php

namespace App\Actions\Shared;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use RuntimeException;
use Throwable;

class PersistUploadedImageAction
{
    /**
     * Previous images are retained for recovery, including soft deletes and restores.
     *
     * @param  array<string, mixed>  $attributes
     */
    public function execute(Model $model, array $attributes, string $field, string $directory, string $disk): Model
    {
        $upload = $attributes[$field] ?? null;
        unset($attributes[$field]);
        $path = null;

        try {
            if ($upload instanceof UploadedFile) {
                $path = $directory.'/'.$upload->hashName();
                if (! Storage::disk($disk)->putFileAs($directory, $upload, basename($path))) {
                    throw new RuntimeException('Image storage failed.');
                }
                $attributes[$field] = $path;
            }

            $model->getConnection()->transaction(function () use ($model, $attributes): void {
                $model->fill($attributes);
                if (! $model->save()) {
                    throw new RuntimeException('Image persistence failed.');
                }
            });
        } catch (Throwable $exception) {
            if ($path !== null) {
                try {
                    if (! Storage::disk($disk)->delete($path)) {
                        report(new RuntimeException('Image compensation failed: '.$disk.':'.$path));
                    }
                } catch (Throwable $cleanupException) {
                    report($cleanupException);
                }
            }
            throw $exception;
        }

        return $model;
    }
}
