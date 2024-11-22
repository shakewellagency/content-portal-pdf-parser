<?php

namespace Shakewellagency\ContentPortalPdfParser\Bases;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

abstract class RenditionBase extends Model
{
    use SoftDeletes;
    protected $table = 'renditions';

    protected $fillable = [
        'rendition_id', 
        'version_id',
        'package_id',
        'type',
        'summary',
        'outline',
        'is_parsed',
        'created_at',
        'updated_at',
    ];
}
