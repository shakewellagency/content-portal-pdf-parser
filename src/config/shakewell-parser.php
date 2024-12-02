<?php


return [
    'env' => env('SHAKEWELL_PARSER_ENV', 'develop'),
    's3' => env('SHAKEWELL_PARSER_S3', 's3'), // Select the S3 disk where the parsed assets will be saved.
];
