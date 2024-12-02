<?php

namespace Shakewellagency\ContentPortalPdfParser\ModelBases;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Shakewellagency\ContentPortalPdfParser\Models\Publication;
use Shakewellagency\ContentPortalPdfParser\Models\Rendition;

abstract class VersionBase extends Model
{
    use SoftDeletes;
    protected $table = 'versions';

    protected $fillable = [
        'publication_id', 
        'title',
        'slug',
        'description',
        'type',
        'system_meta',
        'version_meta',
        'started_at',
        'ended_at',
        'scheduled_at',
        'issue_at',
        'approved',
        'archived',
        'is_current',
        'new_badge',
        'preview_token',
        'approved_token',
        'approved_by',
        'created_at',
        'updated_at',
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
