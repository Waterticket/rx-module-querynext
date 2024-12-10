<?php

namespace Rhymix\Modules\Querynext\Models;

use Exception;
use ReflectionProperty;
use ReflectionObject;
use Rhymix\Modules\Querynext\Models\DBImpl AS DB;

class Entity
{
    private $__original_list = [];
    private $__update_list = [];

    // Implements for php under 8.0
    protected $__table_name = '';
    protected $__id_list = [];

    public function flush(): bool
    {
        $properties_list = $this->getUpdatedPropertiesList();
        if (empty($properties_list))
        {
            return true;
        }

        $table_name = $this->getTableName();
        $id_list = $this->getIdKeyList();

        $id_values = [];
        foreach ($id_list as $id)
        {
            $id_values[$id] = $this->__original_list[$id];
        }

        $oDB = DB::getInstance();
        $oDB->prepare("UPDATE $table_name SET " . implode(', ', array_map(function($key) {
            return "$key = :$key";
        }, array_keys($properties_list)) . " WHERE " . implode(' AND ', array_map(function($key) {
            return "$key = :$key";
        }, array_keys($id_values)))));
        $oDB->bind($properties_list);
        $oDB->bind($id_values);
        $result = $oDB->execute();

        // if key not found, insert
        if ($result->rowCount() === 0)
        {
            $insert_properties_list = array_merge($properties_list, $id_values);
            $oDB->prepare("INSERT INTO $table_name (" . implode(', ', array_keys($insert_properties_list)) . ") VALUES (:" . implode(', :', array_keys($insert_properties_list)) . ")");
            $oDB->bind($insert_properties_list);
            $result = $oDB->execute();
        }

        $is_success = $result->rowCount() > 0;
        if ($is_success)
        {
            foreach ($properties_list as $key => $value)
            {
                $this->__original_list[$key] = $value;
            }
        }

        return $is_success;
    }

    public function delete(): bool
    {
        $table_name = $this->getTableName();
        $id_list = $this->getIdKeyList();

        $id_values = [];
        foreach ($id_list as $id)
        {
            $id_values[$id] = $this->__original_list[$id];
        }

        $oDB = DB::getInstance();
        $oDB->prepare("DELETE FROM $table_name WHERE " . implode(' AND ', array_map(function($key) {
            return "$key = :$key";
        }, array_keys($id_values))));
        $oDB->bind($id_values);
        $result = $oDB->execute();

        $is_success = $result->rowCount() > 0;
        if ($is_success)
        {
            foreach ($id_list as $id)
            {
                unset($this->__original_list[$id]);
            }
        }

        return $is_success;
    }

    private function getUpdatedPropertiesList(): array
    {
        return array_filter($this->__update_list, function($key) {
            return isset($this->__original_list[$key]) && $this->__original_list[$key] !== $this->__update_list[$key];
        }, ARRAY_FILTER_USE_KEY);
    }

    private function getTableName(): string
    {
        if (!empty($this->__table_name))
        {
            return $this->__table_name;
        }

        $reflection = new ReflectionObject($this);
        $table_attr = $reflection->getAttributes(Table::class);
        if (empty($table_attr))
        {
            throw new Exception("Table attribute not found");
        }

        $arguments = $table_attr[0]->getArguments();

        return $arguments[0];
    }

    private function getIdKeyList(): array
    {
        if (!empty($this->__id_list))
        {
            return $this->__id_list;
        }

        $id_list = [];

        $reflection = new ReflectionObject($this);
        $properties = $reflection->getProperties();
        
        foreach ($properties as $property)
        {
            $attributes = $property->getAttributes(Id::class);
            if (!empty($attributes))
            {
            	$name = $property->getName();
                $id_list[] = $name;
            }
        }

        if (empty($id_list))
        {
            throw new Exception("No ID field found");
        }

        return $id_list;
    }

    public function __construct()
    {
        $reflection = new ReflectionObject($this);
        $properties = $reflection->getProperties(ReflectionProperty::IS_PUBLIC | ReflectionProperty::IS_PROTECTED);
        
        foreach ($properties as $property)
        {
            $name = $property->getName();
            if (str_starts_with($name, '__'))
            {
                continue;
            }

            $this->__original_list[$name] = $this->{$name};
        }
    }

    public function __destruct()
    {
        $this->flush();
    }

    public function __set($name, $value)
    {
        $this->__update_list[$name] = $value;

        $uc_name = ucfirst($name);
        $method_name = "set{$uc_name}";
        if ( method_exists($this, $method_name) )
        {
            $this->{$method_name}($value);
            return;
        }

        $method_name = "set_{$name}";
        if ( method_exists($this, $method_name) )
        {
            $this->{$method_name}($value);
            return;
        }

        if ( property_exists($this, $name) )
        {
            $this->{$name} = $value;
            return;
        }

        throw new Exception("Undefined property $name or method $method_name");
    }
}

#[Attribute]
class Id {}

#[Attribute]
class Table {}
