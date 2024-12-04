<?php

namespace Shakewellagency\ContentPortalPdfParser\Traits;

use Illuminate\Support\Facades\Schema;

trait ModelFillableTrait
{
    public function __construct(array $attributes = [])
    {
        parent::__construct($attributes);
        $this->fillable = $this->initFillable();
    }

    protected function initFillable(array $exclude = ['id', 'created_at', 'updated_at','deleted_at'])
    {
        $allColumns = Schema::getColumnListing($this->getTable());
        return array_values(array_diff($allColumns, $exclude));
    }
}

