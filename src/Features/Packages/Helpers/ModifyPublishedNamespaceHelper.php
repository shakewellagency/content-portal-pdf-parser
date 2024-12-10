<?php

namespace Shakewellagency\ContentPortalPdfParser\Features\Packages\Helpers;

class ModifyPublishedNamespaceHelper
{
    public static function modifyPublishedNamespace($path,$oldName, $newName)
    {
        if (is_dir($path)) {
            $files = scandir($path);

            foreach ($files as $file) {
                if (strpos($file, '.php') !== false) {
                    $filePath = $path . DIRECTORY_SEPARATOR . $file;
                    Self::replaceNamespaceInFile($filePath, $oldName, $newName);
                }
            }
        }
    }

    protected static function replaceNamespaceInFile($filePath, $oldNamespace, $newNamespace)
    {
        if (file_exists($filePath)) {
            $content = file_get_contents($filePath);
    
            if (strpos($content, "namespace {$oldNamespace};") !== false) {
                $content = str_replace("namespace {$oldNamespace};", "namespace {$newNamespace};", $content);
                file_put_contents($filePath, $content); 
            }
        }
    }

}

