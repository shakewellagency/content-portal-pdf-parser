<?php

namespace Shakewellagency\ContentPortalPdfParser\ModelBases;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Shakewellagency\ContentPortalPdfParser\Models\Publication;
use Shakewellagency\ContentPortalPdfParser\Models\Rendition;
use Shakewellagency\ContentPortalPdfParser\Traits\ModelFillableTrait;

abstract class VersionBase extends Model
{
    use SoftDeletes;
    use ModelFillableTrait;
    protected $table = 'versions';

    protected $cast = [
        'created_at',
        'updated_at',
        'deleted_at'
    ];

    public function publication()
    {
        return $this->belongsTo(Publication::class);
    }

    public function renditions()
    {
        return $this->hasMany(Rendition::class);
    }
}
