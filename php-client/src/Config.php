<?php

declare(strict_types=1);

namespace App;

final class Config
{
    public static function get(string $key, mixed $default = null): mixed
    {
        $value = getenv($key);

        return $value === false ? $default : $value;
    }

    public static function getArray(string $key, array $default = [], string $separator = ','): array
    {
        $value = self::get($key);

        if ($value === null) {
            return $default;
        }

        return $value
                |> (fn($string) => explode($separator, $string))
                |> (fn($array) => array_map('trim', $array));
    }

    public static function getInt(string $key, int $default = 0): int
    {
        $value = self::get($key);

        return is_numeric($value) ? (int)$value : $default;
    }
}
