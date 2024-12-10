<?php

namespace Rhymix\Modules\Querynext\Models;

use Rhymix\Framework\DB;
use Rhymix\Framework\Helpers\DBResultHelper;

use Rhymix\Modules\Querynext\Models\Jpa\JpaQuery;
use Rhymix\Modules\Querynext\Models\Jpa\JpaQueryBuilder;
use Rhymix\Modules\Querynext\Models\CacheQuery;
use Rhymix\Modules\Querynext\Models\Entity;

class DBImpl extends DB
{
    public function executeJPAQuery(string $jpa_query, array|object $args = [], array $column_list = [], string $result_type = 'auto', string $result_class = 'stdClass'): DBResultHelper
    {
        $cache_query = CacheQuery::getInstance($jpa_query);
        if (!$cache_query->isCached()) {
            $jpa_query = JpaQuery::getInstance($jpa_query, $result_class);

            $oJpaQueryBuilder = JpaQueryBuilder::getInstance($cache_query, $jpa_query);
            $oJpaQueryBuilder->buildQuery();
        }

        $query_name = 'querynext.' . $cache_query->getCacheQueryName();

        $oDB = DB::getInstance();
        return $oDB->executeQuery($query_name, $args, $column_list, $result_type, $result_class);
    }

    public function executeJPAQueryArray(string $jpa_query, array|object $args = [], array $column_list = [], string $result_class = 'stdClass'): DBResultHelper 
    {
        return $this->executeJPAQuery($jpa_query, $args, $column_list, 'array', $result_class);
    }

    public function __construct(string $type = 'master')
    {
        return parent::getInstance($type);
    }

    public static function getInstance(string $type = 'master'): self
    {
        return new self($type);
    }
}