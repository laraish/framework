<?php

namespace Laraish;

use Illuminate\Support\Arr;
use Illuminate\Support\Str;

class Helper
{
    /**
     * @param array $array
     * @param bool $snakeCaseKey
     * @return array|\stdClass
     */
    public static function arrayToObject(array $array, bool $snakeCaseKey = true)
    {
        if (!Arr::isAssoc($array)) {
            foreach ($array as &$value) {
                if (is_array($value)) {
                    $value = static::arrayToObject($value);
                }
            }

            return $array;
        }

        $obj = new \stdClass;
        foreach ($array as $key => $value) {
            if ($snakeCaseKey) {
                $key = str_replace('-', '_', Str::snake($key));
            }
            if (is_array($value)) {
                $obj->{$key} = static::arrayToObject($value);
            } else {
                $obj->{$key} = $value;
            }
        }

        return $obj;
    }

    public static function isArrayOfType($value, string $type): bool
    {
        if (!is_array($value)) {
            return false;
        }

        foreach ($value as $item) {
            if (!($item instanceof $type)) {
                return false;
            }
        }

        return true;
    }
}
