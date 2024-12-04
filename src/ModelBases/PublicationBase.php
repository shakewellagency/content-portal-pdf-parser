<?php

namespace Shakewellagency\ContentPortalPdfParser\ModelBases;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Shakewellagency\ContentPortalPdfParser\Models\Version;
use Shakewellagency\ContentPortalPdfParser\Traits\ModelFillableTrait;

abstract class PublicationBase extends Model
{
    use SoftDeletes;
    use ModelFillableTrait;
    protected $table = 'publications';

    protected $cast = [
        'created_at',
        'updated_at',
        'deleted_at'
    ];

    public function versions()
    {
        return $this->hasMany(Version::class);
    }
}
