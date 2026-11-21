<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreMediaRequest;
use App\Http\Requests\Admin\UpdateMediaRequest;
use App\Http\Resources\Admin\MediaResource;
use App\Models\Media;
use App\Services\ImageSanitizer;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use RuntimeException;
use Throwable;

class MediaController extends Controller
{
    private const int PER_PAGE = 30;

    public function index(): AnonymousResourceCollection
    {
        return MediaResource::collection(Media::query()->latest()->latest('id')->paginate(self::PER_PAGE));
    }

    /**
     * Armazena a imagem com nome aleatório em media/AAAA/MM. O tipo gravado é
     * o detectado no conteúdo, nunca o declarado pelo cliente. Antes de salvar,
     * os metadados (EXIF com GPS etc.) são removidos.
     */
    public function store(StoreMediaRequest $request, ImageSanitizer $sanitizer): JsonResponse
    {
        $file = $request->file('file');
        $mimeType = $file->getMimeType();

        try {
            $sanitizer->sanitize($file->getRealPath(), $mimeType);
        } catch (RuntimeException) {
            return response()->json([
                'message' => 'Não foi possível processar a imagem.',
                'errors' => ['file' => ['Não foi possível processar a imagem. Tente outro arquivo.']],
            ], 422);
        }

        $path = $file->store('media/'.now()->format('Y/m'), Media::DISK);

        if ($path === false) {
            throw new RuntimeException('Não foi possível armazenar a imagem.');
        }

        try {
            [$width, $height] = getimagesize($file->getRealPath()) ?: [null, null];

            $media = Media::create([
                'path' => $path,
                'original_name' => Str::limit($file->getClientOriginalName(), 255, ''),
                'mime_type' => $mimeType,
                'size' => filesize($file->getRealPath()),
                'width' => $width,
                'height' => $height,
                'alt' => $request->validated('alt'),
            ]);
        } catch (Throwable $exception) {
            Storage::disk(Media::DISK)->delete($path);

            throw $exception;
        }

        return (new MediaResource($media))->response()->setStatusCode(201);
    }

    public function show(Media $media): MediaResource
    {
        return new MediaResource($media);
    }

    public function update(UpdateMediaRequest $request, Media $media): MediaResource
    {
        $media->update($request->validated());

        return new MediaResource($media);
    }

    /**
     * Exclui o registro e o arquivo. Imagens em uso (capa ou conteúdo) não são
     * excluídas, para não deixar imagens quebradas no blog.
     */
    public function destroy(Media $media): Response|JsonResponse
    {
        if ($media->isInUse()) {
            return response()->json(['message' => 'A imagem está em uso em um post ou página e não pode ser excluída.'], 409);
        }

        $media->delete();
        Storage::disk(Media::DISK)->delete($media->path);

        return response()->noContent();
    }
}
