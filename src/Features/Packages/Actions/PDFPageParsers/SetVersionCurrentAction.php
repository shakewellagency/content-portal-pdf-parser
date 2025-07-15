<?php

namespace Shakewellagency\ContentPortalPdfParser\Features\Packages\Actions\PDFPageParsers;

use Illuminate\Support\Facades\DB;

class SetVersionCurrentAction
{
    public function execute($version, $rendition)
    {
        return DB::transaction(function () use ($version, $rendition) {
            // Ensure the rendition belongs to the given version
            if ($rendition->version_id !== $version->id) {
                LoggerInfo("rendition:$rendition->id - The specified rendition does not belong to the given version", [
                    'version' => $version->toArray(),
                    'rendition' => $rendition->toArray(),
                ]);

                return $rendition;
            }

            // Unset all current renditions for this version
            $version->renditions()->update(['is_current' => false]);

            // Set the specified rendition as current
            $rendition->is_current = true;
            $rendition->save();

            // Unset all other current versions for the same publication
            $versionModel = config('shakewell-parser.models.version_model');
            $versionModel::where('publication_id', $version->publication_id)
                ->where('id', '!=', $version->id)
                ->update(['is_current' => false]);

            // Set the specified version as current
            $version->is_current = true;
            $version->save();

            // Return fresh instance of updated rendition
            return $rendition->fresh();
        });
    }

}
