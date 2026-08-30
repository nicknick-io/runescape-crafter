<?php

namespace App\Support;

class Feature
{
    public static function enabled(string $feature): bool
    {
        return (bool) config("features.{$feature}", false);
    }
}
