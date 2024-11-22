<?php

namespace Shakewellagency\ContentPortalPdfParser\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Shakewellagency\ContentPortalPdfParser\Bases\PackageBase;

class Package extends PackageBase
{
    use HasFactory,
        SoftDeletes;

    protected $guarded = [
        'id',
    ];

}
