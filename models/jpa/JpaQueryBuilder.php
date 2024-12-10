<?php

namespace Rhymix\Modules\Querynext\Models\Jpa;

use DOMDocument;
use SimpleXMLElement;
use Rhymix\Modules\Querynext\Models\Jpa\JpaQuery;
use Rhymix\Modules\Querynext\Models\CacheQuery;

class JpaQueryBuilder
{
    private JpaQuery $jpa_query;
    private CacheQuery $cache_query;
    private $xml;

    private function __construct(CacheQuery $cache_query, JpaQuery $jpa_query)
    {
        $this->cache_query = $cache_query;
        $this->jpa_query = $jpa_query;
        $this->xml = new SimpleXMLElement('<query></query>');
    }

    public static function getInstance(CacheQuery $cache_query, JpaQuery $jpa_query): self
    {
        return new self($cache_query, $jpa_query);
    }

    public function buildQuery(): void
    {
        if ($this->jpa_query->getMethodType() !== 'SELECT') {
            throw new \Exception('Only SELECT queries are supported');
        }

        $this->buildBase();
        $this->buildTable();
        $this->buildSelectColumns();
        $this->buildWhere();
        $this->buildNavigation();

        $xml_text = $this->xml->asXML();
        $dom = new DOMDocument;
        $dom->preserveWhiteSpace = false;
        $dom->formatOutput = true;
        $dom->loadXML($xml_text);

        $this->cache_query->saveCache($dom->saveXML());
    }

    protected function buildBase(): void
    {
        $this->xml->addAttribute('id', $this->cache_query->getCacheQueryName());
        $this->xml->addAttribute('action', $this->jpa_query->getMethodType());
    }

    protected function buildTable(): void
    {
        $tables = $this->xml->addChild('tables');
        $table = $tables->addChild('table');
        $table->addAttribute('name', $this->jpa_query->getQueryDtoNameUnderscore());
    }

    protected function buildSelectColumns(): void
    {
        $columns = $this->xml->addChild('columns');
        $column = $columns->addChild('column');
        $column->addAttribute('name', '*');
    }

    protected function buildWhere(): void
    {
        $conditions = $this->xml->addChild('conditions');

        foreach ($this->jpa_query->getSelectFields() as $select_field) {
            $condition = $conditions->addChild('condition');
            $condition->addAttribute('operation', self::getOperation($select_field['keyword']));
            $condition->addAttribute('column', $select_field['field_name']);
            $condition->addAttribute('var', $select_field['field_name']);
            $condition->addAttribute('pipe', $select_field['pipe']);
        }
    }

    protected static function getOperation(string $keyword): string
    {
        if (str_starts_with($keyword, 'is') && strlen($keyword) > 2) {
            $keyword = substr($keyword, 1);
        }

        switch ($keyword) {
            case 'is':
            case 'eq':
            case 'equal':
                return 'equal';

            case 'between':
                return 'between';

            case 'notbetween':
                return 'notbetween';

            case 'notequal':
            case 'not':
                return 'notequal';

            case 'equals':
            case 'in':
                return 'in';

            case 'notin':
                return 'notin';
            
            case 'le':
            case 'lte':
            case 'lessthanequal':
                return 'lte';
            
            case 'lt':
            case 'lessthan':
            case 'before':
                return 'lt';

            case 'ge':
            case 'gte':
            case 'greaterthanequal':
                return 'gte';

            case 'gt':
            case 'greaterthan':
            case 'after':
                return 'gt';

            case 'null':
                return 'null';

            case 'notnull':
                return 'notnull';

            case 'like':
            case 'contains':
            case 'containing':
                return 'like';

            case 'notlike':
            case 'notcontains':
            case 'notcontaining':
                return 'notlike';

            case 'startingwith':
                return 'like_prefix';

            case 'notstartingwith':
                return 'notlike_prefix';

            case 'endingwith':
                return 'like_suffix';

            case 'notendingwith':
                return 'notlike_suffix';

            default:
                throw new \Exception('Unknown operation: ' . $keyword);
        }
    }

    protected function buildNavigation(): void
    {
        $navigation = $this->xml->addChild('navigation');

        // 일반 쿼리
        if (!$this->jpa_query->isOrderBy() && !$this->jpa_query->isPageable()) {
            return;
        }

        $this->buildOrderBy($navigation);

        if (!$this->jpa_query->isPageable()) {
            return;
        }

        $list_count = $navigation->addChild('list_count');
        $list_count->addAttribute('var', 'list_count');
        $list_count->addAttribute('default', '20');

        $page_count = $navigation->addChild('page_count');
        $page_count->addAttribute('var', 'page_count');
        $page_count->addAttribute('default', '10');

        $page = $navigation->addChild('page');
        $page->addAttribute('var', 'page');
        $page->addAttribute('default', '1');
    }

    protected function buildOrderBy($navigation): void
    {
        $order_by_target = $this->jpa_query->getOrderByTarget();
        $order_by_direction = $this->jpa_query->getOrderByDirection();

        if (!$this->jpa_query->isOrderBy()) { // default order
            $order_by_direction = 'order_type';
        }

        $index_column = $navigation->addChild('index');
        $index_column->addAttribute('var', 'sort_index');
        $index_column->addAttribute('default', $order_by_target);
        $index_column->addAttribute('order', $order_by_direction);
    }
}
