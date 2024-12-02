<?php

namespace Shakewellagency\ContentPortalPdfParser\ModelBases;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Shakewellagency\ContentPortalPdfParser\Models\Rendition;

abstract class PackageBase extends Model
{
    use SoftDeletes;
    protected $table = 'packages';

    protected $fillable = [
        'file_type', 
        'file_name',
        'hash',
        'status',
        'location',
        'file_path',
        'request_ip',
        'parser_version',
        'initiated_by',
        'started_at',
        'finished_at',
        'failed_exception',
    ];

    public function rendition()
    {
        return $this->hasOne(Rendition::class);
    }
}
