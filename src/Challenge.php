<?php

namespace Qruto\Cave;

use Stringable;

class Challenge implements Stringable
{
    private static string $fakeValue;

    public function __construct(private int $length = 16)
    {
    }

    public static function fake()
    {
        self::$fakeValue = random_bytes(16);

        return self::$fakeValue;
    }

    public function __toString()
    {
        return self::$fakeValue ?? random_bytes($this->length);
    }
}
