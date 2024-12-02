<?php

namespace Shakewellagency\ContentPortalPdfParser\ModelBases;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Shakewellagency\ContentPortalPdfParser\Models\Rendition;

abstract class RenditionPageBase extends Model
{
    use SoftDeletes;
    protected $table = 'rendition_pages';

    protected $fillable = [
        'rendition_id', 
        'slug',
        'page_no',
        'content',
        'is_parsed',
        'created_at',
        'updated_at',
    ];

    public function rendition()
    {
        return $this->belongsTo(Rendition::class);
    }
}



