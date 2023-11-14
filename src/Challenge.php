<?php

namespace Qruto\Cave;

use Stringable;

class Challenge implements Stringable
{
    public function __construct(private int $length = 16)
    {
    }

    public function __toString()
    {
        return random_bytes($this->length);
    }
}
