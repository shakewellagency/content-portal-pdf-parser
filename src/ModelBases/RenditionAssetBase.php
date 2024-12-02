<?php

namespace Shakewellagency\ContentPortalPdfParser\ModelBases;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Shakewellagency\ContentPortalPdfParser\Models\Rendition;

abstract class RenditionAssetBase extends Model
{
    use SoftDeletes;
    protected $table = 'rendition_assets';

    protected $fillable = [
        'rendition_id', 
        'type',
        'file_name',
        'file_path',
        'created_at',
        'updated_at',
    ];

    public function rendition()
    {
        return $this->belongsTo(Rendition::class);
    }
}
