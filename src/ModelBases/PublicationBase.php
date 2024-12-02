<?php

namespace Shakewellagency\ContentPortalPdfParser\ModelBases;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Shakewellagency\ContentPortalPdfParser\Models\Version;

abstract class PublicationBase extends Model
{
    use SoftDeletes;
    protected $table = 'publications';

    protected $fillable = [
        'publication_no', 
        'title',
        'slug',
        'description',
        'type',
        'doc_type'.
        'link',
        'new_badge',
        'created_at',
        'updated_at',
        'deleted_at',
    ];


    public function versions()
    {
        return $this->hasMany(Version::class);
    }
}
