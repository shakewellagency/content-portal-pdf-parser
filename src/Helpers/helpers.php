<?php
if (! function_exists('LoggerInfo')) {

    function LoggerInfo($message, array $data = []): void
    {
        activity()
            ->withProperties($data)
            ->log($message);
    }
}
