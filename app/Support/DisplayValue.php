<?php

namespace App\Support;

use BackedEnum;
use JsonSerializable;
use Stringable;

class DisplayValue
{
    public static function make(mixed $value): string
    {
        if (is_null($value)) {
            return '';
        }

        if (is_bool($value)) {
            return $value ? 'Yes' : 'No';
        }

        if ($value instanceof BackedEnum) {
            return (string) $value->value;
        }

        if ($value instanceof Stringable) {
            return (string) $value;
        }

        if ($value instanceof JsonSerializable) {
            return self::make($value->jsonSerialize());
        }

        if (is_array($value)) {
            $isList = array_is_list($value);

            return collect($value)
                ->map(function (mixed $item, mixed $key) use ($isList): string {
                    $display = self::make($item);

                    return $isList ? $display : str_replace('_', ' ', (string) $key).': '.$display;
                })
                ->filter(fn (string $item) => $item !== '')
                ->implode(', ');
        }

        if (is_scalar($value)) {
            return (string) $value;
        }

        return '[structured value]';
    }
}
