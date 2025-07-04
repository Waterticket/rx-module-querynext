<?php

namespace Rhymix\Modules\Querynext\Models\Jpa\Attributes;

class Attribute
{
    private array $value;

    public function __construct(array $value)
    {
        $this->value = $value;
    }

    protected function getValue(string $key)
    {
        return $this->value[$key];
    }
}