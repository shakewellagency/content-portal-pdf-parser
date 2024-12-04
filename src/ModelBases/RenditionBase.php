<?php

namespace Shakewellagency\ContentPortalPdfParser\ModelBases;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Shakewellagency\ContentPortalPdfParser\Models\Package;
use Shakewellagency\ContentPortalPdfParser\Models\RenditionAsset;
use Shakewellagency\ContentPortalPdfParser\Models\RenditionPage;
use Shakewellagency\ContentPortalPdfParser\Models\Version;
use Shakewellagency\ContentPortalPdfParser\Traits\ModelFillableTrait;

abstract class RenditionBase extends Model
{
    use SoftDeletes;
    use ModelFillableTrait;

    protected $table = 'renditions';

    protected $cast = [
        'created_at',
        'updated_at',
        'deleted_at'
    ];

    public function package()
    {
        return $this->belongsTo(Package::class);
    }

    public function version()
    {
        return $this->belongsTo(Version::class);
    }

    public function renditionPages()
    {
        return $this->hasMany(RenditionPage::class);
    }

    public function renditionAssets()
    {
        return $this->hasMany(RenditionAsset::class);
    }
}
