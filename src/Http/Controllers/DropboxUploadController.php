<?php declare(strict_types=1);

namespace DmitryBubyakin\NovaMedialibraryField\Http\Controllers;

use DmitryBubyakin\NovaMedialibraryField\Http\Requests\MedialibraryRequest;
use DmitryBubyakin\NovaMedialibraryField\TransientModel;
use Illuminate\Http\JsonResponse;

class DropboxUploadController
{
    public function __invoke(MedialibraryRequest $request): JsonResponse
    {
        [$model, $uuid] = $request->resourceExists()
            ? [$request->findModelOrFail(), '']
            : [TransientModel::make(), $request->fieldUuid()];

        $field = $request->medialibraryField();

        $request->validate([
            'file_name' => 'required|string',
            'file_url' => 'required|url'
        ]);

        if (!$file = file_get_contents($request->file_url, false)) {
            return response()->json(['message' => 'Could not read file from Dropbox'], 404);
        }

        if (!$result = $model->addMediaFromString($file)
            ->usingFileName($request->file_name)
            ->toMediaCollection('gallery')) {
            return response()->json(['message' => 'Failed to upload Dropbox file to server'], 502);
        }

        return response()->json(['id' => $result->id]);
    }
}
