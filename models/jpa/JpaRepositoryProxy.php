<?php

namespace Rhymix\Modules\Querynext\Models\Jpa;

use stdClass;
use ReflectionClass;
use Rhymix\Framework\Exceptions;
use Rhymix\Framework\Helpers\DBResultHelper;
use Rhymix\Modules\Querynext\Models\DBImpl;
use Rhymix\Modules\Querynext\Models\Jpa\JpaQueryRepository;
use Rhymix\Modules\Querynext\Models\Jpa\Attributes\Table;

class JpaRepositoryProxy
{
    private static $method_cache = [];

    public static function __callStatic($name, $arguments)
    {
        $backtrace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 2);
        $calledClass = $backtrace[1]['class'] ?? null;

        if (!$calledClass || !is_subclass_of($calledClass, JPARepository::class)) {
            throw new \Exception('Invalid call');
        }

        $reflection = new ReflectionClass($calledClass);
        $class_full_name = $reflection->getNamespaceName() . '\\' . $reflection->getName();
        $method_key = sha1($class_full_name . '::' . $name);
        if (!isset(self::$method_cache[$method_key])) {
            // get table name
            $attributes = $reflection->getAttributes(Table::class);
            if (empty($attributes)) {
                throw new \Exception('Table attribute not found');
            }
    
            $table_class = $attributes[0]->newInstance();
            $table = self::snakeToCamel($table_class->getTableName());
    
            // get return type
            $return_type = $reflection->getMethod($name)->getReturnType();
            $return_type_list = explode('|', $return_type);

            // get function arguments
            $args = $reflection->getMethod($name)->getParameters();
            $args_list = []; // [['name'=>'age', 'type'=>'int', 'default'=>null], ...]
            foreach ($args as $arg) {
                $args_list[] = [
                    'name' => $arg->getName(),
                    'type' => $arg->getType(),
                    'default' => $arg->isDefaultValueAvailable() ? $arg->getDefaultValue() : null,
                ];
            }

            // get new query name
            $query = JpaQueryRepository::getInstance($name);
            $first_by_position = strpos($name, 'By');
            $query_type = '';
            if ($query->isList()) {
                $query_type .= 'List';
            }
            if ($query->isPageable()) {
                $query_type .= 'Pageable';
            }
    
            $type = strtolower($query->getMethodType());
            $new_query = strtolower($reflection->getName()) . '.' . $type . ucfirst($table) . $query_type . substr($name, $first_by_position);

            // get request column list
            $request_column_list = $query->getColumnList();

            self::$method_cache[$method_key] = [$new_query, $request_column_list, $return_type_list, $args_list];
        }

        [$query, $request_column_list, $return_type_list, $args_list] = self::$method_cache[$method_key];

        $array_arguments = self::setArgumentsToArrayMap($args_list, $arguments);

        $oDB = DBImpl::getInstance();
        $output = $oDB->executeJPAQuery($query, $array_arguments, $request_column_list, 'auto');
        return self::returnResult($output, $request_column_list, $return_type_list);
    }

    private static function returnResult(DBResultHelper $output, array $request_column_list, array $return_type_list)
    {
        if (empty($return_type_list) || str_contains('DBResultHelper', $return_type_list[0])) {
            return $output;
        }

        $return_type = $return_type_list[0];
        if (!$output->toBool()) {
            throw new Exceptions\DBError($output->getMessage());
        }

        $first_row = $output->data[0] ?? null;

        switch ($return_type) {
            case 'int':
                if (empty($first_row)) {
                    return null;
                }

                return (int) $first_row[$request_column_list[0]];

            case 'float':
                if (empty($first_row)) {
                    return null;
                }

                return (float) $first_row[$request_column_list[0]];

            case 'string':
                if (empty($first_row)) {
                    return null;
                }

                return $first_row[$request_column_list[0]];

            case 'array':
                if (is_array($output->data)) {
                    return $output->data;
                }

                return [$output->data];

            case 'object':
            case 'stdClass':
                if (empty($first_row)) {
                    return new stdClass();
                }

                return (object) $first_row;

            default:
                throw new \Exception('Invalid return type');
        }
    }

    private static function setArgumentsToArrayMap($args_list, $arguments)
    {
        $array_arguments = [];
        foreach ($args_list as $idx => $arg) {
            $array_arguments[$arg['name']] = $arguments[$idx] ?? $arg['default'];
        }

        return $array_arguments;
    }

    private static function snakeToCamel($input)
    {
        return lcfirst(str_replace(' ', '', ucwords(str_replace('_', ' ', $input))));
    }

    private function __construct()
    {
    }
}