<?php


return [
    'env' => env('SHAKEWELL_PARSER_ENV', 'develop'),
    's3' => env('SHAKEWELL_PARSER_S3', 's3'), 
    'publication_model' => '', //App\Features\Publications\Models\Publication::class 
    'version_model' => '', //App\Features\Versions\Models\Version::class,
    'package_model' => '', //App\Features\Packages\Models\Package::class,
    'rendition_model' => '', //App\Features\Renditions\Models\Rendition::class,
    'rendition_asset_model' => '', //App\Features\Renditions\Models\RenditionAsset::class,
    'rendition_page_model' => '', //App\Features\Renditions\Models\RenditionPage::class,
];
