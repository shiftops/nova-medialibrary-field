<?php declare(strict_types=1);

namespace DmitryBubyakin\NovaMedialibraryField\Http\Controllers;

use DmitryBubyakin\NovaMedialibraryField\Http\Requests\MedialibraryRequest;
use DmitryBubyakin\NovaMedialibraryField\TransientModel;
use Illuminate\Http\JsonResponse;

class DropboxUploadController
{
    /**
     * @param MedialibraryRequest $request
     * @return JsonResponse
     */
    public function __invoke(MedialibraryRequest $request): JsonResponse
    {
        [$model, $uuid] = $request->resourceExists()
            ? [$request->findModelOrFail(), '']
            : [TransientModel::make(), $request->fieldUuid()];

        $request->validate([
            'file_name' => 'required|string',
            'file_url' => 'required|url',
            'attribute' => 'required|string',
            'component' => 'required|string',
            'collection_name' => 'required|string',
        ]);

        if (!$file = file_get_contents($request->file_url, false)) {
            return response()->json(['message' => 'Could not read file from Dropbox'], 404);
        }

        if (!$result = $model->addMediaFromString($file)
            ->usingFileName($request->file_name)
            ->toMediaCollection($request->collection_name)) {
            return response()->json(['message' => 'Failed to upload Dropbox file to server'], 502);
        }

        // Here is my dodgy way of fetching and saving the flexibleField key
        if ($request->component === 'nova-medialibrary-field'
            && stristr($request->attribute, '__')
            && ($attributeParts = explode('__', $request->attribute))
            && count($attributeParts) === 2) {
            $result->custom_properties = [
                'flexibleKey' => $attributeParts[0]
            ];
            $result->update();
        }

        return response()->json(['id' => $result->id]);
    }
}
