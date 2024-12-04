<?php

namespace Shakewellagency\ContentPortalPdfParser\ModelBases;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Shakewellagency\ContentPortalPdfParser\Models\Rendition;
use Shakewellagency\ContentPortalPdfParser\Traits\ModelFillableTrait;

abstract class RenditionAssetBase extends Model
{
    use SoftDeletes;
    use ModelFillableTrait;

    protected $table = 'rendition_assets';

    protected $cast = [
        'created_at',
        'updated_at',
        'deleted_at'
    ];

    public function rendition()
    {
        return $this->belongsTo(Rendition::class);
    }
}
