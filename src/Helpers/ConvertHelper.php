<?php

namespace Shakewellagency\ContentPortalPdfParser\Helpers;


class ConvertHelper
{
    public static function safeGet(
        $model, 
        array $parameter, 
        string $field, 
        $bool = false
    ){

        if (isset($parameter[$field])) {
            return $parameter[$field];
        }

        if ($model->{$field}) {
            return $model->{$field};
        }
        
        return $bool ? false : null;
    }
}