<?php

namespace Shakewellagency\ContentPortalPdfParser\ServiceProviders;

use Illuminate\Support\ServiceProvider;

class ParserServiceProvider extends ServiceProvider
{
    public function register()
    {
        // You can register other bindings here if necessary
    }

    public function boot()
    {
        $this->publishes([
            __DIR__.'/../databases/migrations' => database_path('migrations'),
            __DIR__.'/../Enums' => app_path('Enums'),
            __DIR__ . '/../config/shakewell-parser.php' => config_path('shakewell-parser.php'),
        ], 'parser-assets');

        $this->modifyPublishedNamespace(app_path('Enums'), 'Shakewellagency\\ContentPortalPdfParser\\Enums', 'App\\Enums');
    }

    protected function modifyPublishedNamespace($path,$oldName, $newName)
    {
        if (is_dir($path)) {
            $files = scandir($path);

            foreach ($files as $file) {
                if (strpos($file, '.php') !== false) {
                    $filePath = $path . DIRECTORY_SEPARATOR . $file;
                    $this->replaceNamespaceInFile($filePath, $oldName, $newName);
                }
            }
        }
    }



    protected function replaceNamespaceInFile($filePath, $oldNamespace, $newNamespace)
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
