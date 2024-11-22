<?php

namespace Shakewellagency\ContentPortalPdfParser\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Shakewellagency\ContentPortalPdfParser\Bases\VersionBase;

class Version extends VersionBase
{
    use HasFactory,
        SoftDeletes;

    protected $guarded = [
        'id',
    ];

}
