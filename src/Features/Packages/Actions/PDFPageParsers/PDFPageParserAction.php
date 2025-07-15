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

        $command = "pdftohtml -c -hidden -noframes -f {$page} -l {$page} -zoom 2.78 {$parserFile} {$tempHtmlPath}";
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

                // Clean invalid UTF-8 sequences without altering valid content
                $htmlString = iconv('UTF-8', 'UTF-8//IGNORE', $htmlString);

                // Remove outline if not on page 1
                $htmlString = $page == 1 ? $htmlString : ContentParserHelper::removeOutline($htmlString);

                // Optional: check string size if needed
                if (strlen($htmlString) > 5000000) { // 5MB threshold
                    Log::error('HTML string too large to encode', [
                        'size' => strlen($htmlString),
                        'rendition_page_id' => $renditionPage->id,
                    ]);
                    throw new \Exception('HTML string too large to encode');
                }

                // Encode to JSON and verify result
                $jsonContent = json_encode($htmlString);

                if ($jsonContent === false) {
                    Log::error('Invalid JSON encoding for rendition page.', [
                        'error' => json_last_error_msg(),
                        'rendition_page_id' => $renditionPage->id,
                    ]);

                    throw new \Exception('Failed to encode JSON: ' . json_last_error_msg());
                }

                // Save content and mark as parsed
                $renditionPage->content = $jsonContent;
                $renditionPage->is_parsed = true;
                $renditionPage->save();
            }
            
            $s3Path = "{$package->hash}/assets/{$filename}";
            
            if ($extension != 'html') {
                $renditionAssetTypeEnum = config('shakewell-parser.enums.rendition_asset_type_enum');
                Storage::disk(config('shakewell-parser.s3'))->put($s3Path, file_get_contents($file));
                $this->createAsset($renditionPage->rendition_id, $filename, $renditionAssetTypeEnum::Image->value, $s3Path, $page);
            }

            unlink($file);
        }

        return $renditionPage;
    }

    private function createAsset($renditionId, $fileName, $fileType, $filePath, $page) 
    {
        $renditionAssetModel = config('shakewell-parser.models.rendition_asset_model');
        $asset = new $renditionAssetModel;
        $asset->rendition_id = $renditionId;
        $asset->type = $fileType;
        $asset->page_no = $page;
        $asset->file_name = $fileName;
        $asset->file_path = $filePath;
        $asset->save();
    }

}
