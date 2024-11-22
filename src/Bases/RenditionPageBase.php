<?php

namespace Shakewellagency\ContentPortalPdfParser\Bases;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

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
}
