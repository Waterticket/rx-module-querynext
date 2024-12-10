<?php

namespace Rhymix\Modules\Querynext\Models\Jpa;

use Exception;

class JpaQuery {
    private $method_type; // SELECT, UPDATE, DELETE
    private $dto_class; // DTO class name
    private $dto_name; // DTO variable name
    private $query_dto_name; // DTO variable name in query
    private $query_dto_name_underscore; // DTO variable name in query with underscore
    private $select_fields = []; // [['field_name'=>'age', 'keyword'=>'between', 'pipe'=>'and',], ...]
    private $is_list = false;
    private $is_pageable = false;
    private $is_order_by = false;
    private $order_by_target = '';
    private $order_by_direction = 'asc';

    private $jpa_query = '';

    public static function getInstance(string $jpa_query, $dto_class = 'stdClass'): self
    {
        return new self($jpa_query, $dto_class);
    }

    private function __construct(string $jpa_query, $dto_class = 'stdClass') {
        if (empty($jpa_query)) {
            throw new Exception('JPA query cannot be empty');
        }

        if (is_string($dto_class)) {
            if (!class_exists($dto_class)) {
                throw new Exception('DTO class does not exist: ' . $dto_class);
            }

            $this->dto_class = new $dto_class();
        } else {
            if (!is_object($dto_class)) {
                throw new Exception('DTO class should be a class name or an object');
            }

            $this->dto_class = $dto_class;
        }

        $class_name = get_class($this->dto_class);
        $this->dto_name = strtolower($class_name);

        $this->jpa_query = $jpa_query;
        $this->jpaMethodParser();
    }

    private function jpaMethodParser() {
        // check if method name is camel case
        if (!preg_match('/[a-z]+/', $this->jpa_query)) {
            throw new Exception('Method name should be in camel case');
        }

        $post_process_jpa_query = $this->jpa_query;

        // check is order by is present
        if (strpos($this->jpa_query, 'OrderBy') !== false) {
            $post_process_jpa_query = substr($this->jpa_query, 0, strpos($this->jpa_query, 'OrderBy'));
            $order_by_str = substr($this->jpa_query, strpos($this->jpa_query, 'OrderBy') + 7);
            $this->parseOrderBy($order_by_str);
        }

        $method_parts = preg_split('/(?=[A-Z])/', $post_process_jpa_query, -1, PREG_SPLIT_NO_EMPTY);
        $method_parts = array_map('strtolower', $method_parts);
    
        $this->parseMethodType($method_parts[0]);
        $method_parts = array_slice($method_parts, 1);

        $after_by_idx = $this->parseMethodDtoName($method_parts);
        $method_parts = array_slice($method_parts, $after_by_idx);

        $this->parseSelectFields($method_parts);
    }

    private function parseMethodDtoName(array $method_parts): int {
        $dto_name_array = [];
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

            if ($part == 'by') {
                $dto_name_idx = $idx + 1; // skip 'by'
                break;
            }

            $dto_name_array[] = $part;
        }

        $dto_name = implode('', $dto_name_array);
        $dto_name_underscore = implode('_', $dto_name_array);

        $this->query_dto_name = $dto_name;
        $this->query_dto_name_underscore = $dto_name_underscore;

        if ($this->dto_name !== 'stdclass' && !empty($dto_name) && $this->dto_name !== strtolower($dto_name)) {
            throw new Exception('DTO name does not match');
        }

        return $dto_name_idx;
    }

    private function parseMethodType(string $keyword): void {
        $this->method_type = 'SELECT';

        switch ($keyword) {
            case 'get':
            case 'find':
            case 'select':
                $this->method_type = 'SELECT';
                break;
            case 'update':
                $this->method_type = 'UPDATE';
                break;
            case 'delete':
                $this->method_type = 'DELETE';
                break;
        }
    }

    private function getInitialField(string $part = ''): array {
        return [
            'pipe' => $part,
            'keyword' => '',
            'field_name' => '',
            'field_name_arr' => [],
        ];
    }

    private function parseSelectFields(array $method_parts): void {
        $select_fields = [];
        $field = $this->getInitialField();

        foreach ($method_parts as $part) {
            if (in_array($part, ['and', 'or'])) {
                $select_fields[] = $field;
                $field = $this->getInitialField($part);
                continue;
            }

            if (in_array($part, ['is', 'equals', 'between', 'less', 'than', 'equal', 'greater', 'after', 'before', 'null', 'not', 'like', 'starting', 'with', 'ending', 'true', 'false', 'in', 'eq', 'ne', 'gt', 'lt', 'ge', 'le'])) {
                $field['keyword'] .= $part;
                continue;
            }

            $field['field_name_arr'][] = $part;
        }

        $select_fields[] = $field; // add last field

        foreach ($select_fields as $key => &$field) {
            $field['field_name'] = implode('_', $field['field_name_arr']);
            unset($field['field_name_arr']);

            if (empty($field['keyword'])) {
                $field['keyword'] = 'equal';
            }
        }

        $this->select_fields = $select_fields;
    }

    private function parseOrderBy(string $order_by_str): void {
        $order_by_parts = preg_split('/(?=[A-Z])/', $order_by_str, -1, PREG_SPLIT_NO_EMPTY);
        $order_by_parts = array_map('strtolower', $order_by_parts);
        $this->is_order_by = true;

        $last_word = $order_by_parts[count($order_by_parts) - 1];
        if (in_array($last_word, ['asc', 'desc'])) {
            $this->order_by_direction = $last_word;
            $order_by_parts = array_slice($order_by_parts, 0, count($order_by_parts) - 1);
        }

        $this->order_by_target = implode('_', $order_by_parts);
    }

    // Getters
    public function getMethodType(): string {
        return $this->method_type;
    }

    public function getDtoClass() {
        return $this->dto_class;
    }

    public function getQueryDtoName(): string {
        return $this->query_dto_name;
    }

    public function getQueryDtoNameUnderscore(): string { // table name
        return $this->query_dto_name_underscore;
    }

    public function getSelectFields(): array {
        return $this->select_fields;
    }

    public function isList(): bool {
        return $this->is_list;
    }

    public function isPageable(): bool {
        return $this->is_pageable;
    }

    public function isOrderBy(): bool {
        return $this->is_order_by;
    }

    public function getOrderByTarget(): string {
        return $this->order_by_target;
    }

    public function getOrderByDirection(): string {
        return $this->order_by_direction;
    }
}
