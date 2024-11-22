<?php

namespace Shakewellagency\ContentPortalPdfParser\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Shakewellagency\ContentPortalPdfParser\ModelBases\RenditionAssetBase;

class RenditionAsset extends RenditionAssetBase
{
    use HasFactory,
        SoftDeletes;

    protected $guarded = [
        'id',
    ];

}
