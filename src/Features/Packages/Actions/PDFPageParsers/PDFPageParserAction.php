<?php

namespace Shakewellagency\ContentPortalPdfParser\Features\Packages\Actions\PDFPageParsers;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Shakewellagency\ContentPortalPdfParser\Features\Packages\Exceptions\ConversionFailedException;
use Shakewellagency\ContentPortalPdfParser\Features\Packages\Helpers\ContentParserHelper;

class PDFPageParserAction
{
    public function execute(
        $page, 
        $parserFile, 
        $renditionPage, 
        $package
    ){

        $tempNamePrefix = "parser_{$package->hash}_";
        $tempHtmlFile = tmpfile();
        $tempHtml = tempnam(sys_get_temp_dir(), $tempNamePrefix);
        $tempHtmlPath = $tempHtml .'_page_'.$page. '.html';
        rename($tempHtml, $tempHtmlPath);

        $command = "pdftohtml -c -hidden -noframes -f {$page} -l {$page} -zoom 1.5 {$parserFile} {$tempHtmlPath}";
        exec($command . ' 2>&1', $output, $return_var);
        
        if ($return_var !== 0) {
            fclose($tempHtmlFile);
            LoggerInfo("package:$package->id - Failed to Convert the PDF Page to HTML", [
                'package' => $package->toArray(),
                'renditionPage' => $renditionPage,
            ]);
            throw new ConversionFailedException('Failed to Convert the PDF Page to HTML');
        }

        $tempDirectory = dirname($tempHtml);
        $files = glob($tempDirectory . "/{$tempNamePrefix}*.*");

        foreach ($files as $file) {
            $filename = basename($file);
            $extension = pathinfo($file, PATHINFO_EXTENSION);
            
            if ($extension === 'html') {
                $htmlString = file_get_contents($file);
                $htmlString = $page == 1 ? $htmlString : ContentParserHelper::removeOutline($htmlString);

                $renditionPage->content = json_encode($htmlString);

                if ($htmlString) {
                    $renditionPage->original_content = json_encode($htmlString);
                }
                
                $renditionPage->is_parsed = true;
                $renditionPage->save();
            }
            
            $s3Path = "{$package->hash}/assets/{$filename}";
            
            if ($extension != 'html') {
                $renditionAssetTypeEnum = config('shakewell-parser.enums.rendition_asset_type_enum');
                Storage::disk(config('shakewell-parser.s3'))->put($s3Path, file_get_contents($file));
                $this->createAsset($renditionPage->rendition_id, $filename, $renditionAssetTypeEnum::Image->value, $s3Path);
            }

            unlink($file);
        }

        
        return $renditionPage;
    }

    private function createAsset($renditionId, $fileName, $fileType, $filePath) 
    {
        $renditionAssetModel = config('shakewell-parser.models.rendition_asset_model');
        $asset = new $renditionAssetModel;
        $asset->rendition_id = $renditionId;
        $asset->type = $fileType;
        $asset->file_name = $fileName;
        $asset->file_path = $filePath;
        $asset->save();
    }

}
