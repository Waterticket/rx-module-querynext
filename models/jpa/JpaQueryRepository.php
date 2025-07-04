<?php

namespace Rhymix\Modules\Querynext\Models\Jpa;

use Exception;

class JpaQueryRepository extends JpaQuery {
    private array $column_list = [];

    protected function parseMethodDtoName(array $method_parts): int {
        $local_column_list = [];
        $local_column = [];
        $dto_name_idx = 0;
        foreach ($method_parts as $idx => $part) {
            if (in_array($part, ['pageable'])) {
                $this->is_pageable = true;
                continue;
            }

            if (in_array($part, ['list'])) {
                $this->is_list = true;
                continue;
            }

            if (in_array($part, ['and'])) {
                $local_column_list[] = $local_column;
                $local_column = [];
                continue;
            }

            if ($part == 'by') {
                $dto_name_idx = $idx + 1; // skip 'by'
                break;
            }

            $local_column[] = $part;
        }

        $local_column_list[] = $local_column;

        foreach ($local_column_list as $column) {
            $column_name_underscore = implode('_', $column);
            $this->column_list[] = $column_name_underscore;
        }

        return $dto_name_idx;
    }

    public function getColumnList(): array {
        return $this->column_list;
    }

    public static function getInstance(string $jpa_query, $dto_class = 'stdClass'): JpaQueryRepository {
        return new self($jpa_query, $dto_class);
    }

    private function __construct(string $jpa_query, $dto_class = 'stdClass') {
        parent::getInstance($jpa_query, $dto_class);
    }
}