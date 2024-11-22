<?php

namespace Shakewellagency\ContentPortalPdfParser\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Shakewellagency\ContentPortalPdfParser\Bases\RenditionBase;

class Rendition extends RenditionBase
{
    use HasFactory,
        SoftDeletes;

    protected $guarded = [
        'id',
    ];

}
