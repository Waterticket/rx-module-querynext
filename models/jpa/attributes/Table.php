<?php

namespace Rhymix\Modules\Querynext\Models\Jpa\Attributes;

use Rhymix\Modules\Querynext\Models\Jpa\Attributes\Attribute;

class Table extends Attribute
{
    public function __construct(string $table_name)
    {
        parent::__construct(['table_name' => $table_name]);
    }

    public function getTableName(): string
    {
        return $this->getValue('table_name');
    }
}