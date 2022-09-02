<?php

namespace Smoren\QueryRelationManager\Base\Structs;

use Countable;
use IteratorAggregate;
use Smoren\QueryRelationManager\Base\QueryRelationManagerException;
use Traversable;

/**
 * Collection of tables used in select-query
 * @author Smoren <ofigate@gmail.com>
 * @implements IteratorAggregate<Table>
 */
class TableCollection implements Countable, IteratorAggregate
{
    /**
     * @var Table|null main table object
     */
    protected ?Table $mainTable = null;

    /**
     * @var Table[] map of tables indexed by table alias in query
     */
    protected array $mapByAlias = [];

    /**
     * Adds table object to the collection
     * @param Table $table table used in query
     * @return $this
     * @throws QueryRelationManagerException
     */
    public function add(Table $table): self
    {
        if($this->mainTable === null) {
            $this->mainTable = $table;
        }

        $this->addToMap('mapByAlias', 'alias', $table);

        return $this;
    }

    /**
     * Returns main table of the query
     * @return Table
     * @throws QueryRelationManagerException
     */
    public function getMainTable(): Table
    {
        if($this->mainTable === null) {
            throw new QueryRelationManagerException('no main table found in TableManager');
        }

        return $this->mainTable;
    }

    /**
     * Returns table object by it's alias in query
     * @param string $alias table alias in query
     * @return Table
     * @throws QueryRelationManagerException
     */
    public function byAlias(string $alias): Table
    {
        return $this->getFromMap('mapByAlias', $alias);
    }

    /**
     * Returns chain of the primary key's fields of all the joined tables till given by alias
     * @param string $tableAlias table alias
     * @param JoinConditionCollection $joinConditions collection of join conditions
     * @return array<string>
     * @throws QueryRelationManagerException
     */
    public function getPkFieldChain(string $tableAlias, JoinConditionCollection $joinConditions): array
    {
        $tableAliasChain = $this->getTableAliasChain($tableAlias, $joinConditions);
        $result = [];

        foreach($tableAliasChain as $alias) {
            $table = $this->byAlias($alias);

            foreach($table->primaryKey as $field) {
                $result[] = "{$alias}_{$field}";
            }
        }

        return $result;
    }

    /**
     * Returns chain of aliases of all the joined tables till given by alias
     * @param string $tableAlias table alias
     * @param JoinConditionCollection $joinConditions collection of join conditions
     * @return array<string>
     */
    public function getTableAliasChain(string $tableAlias, JoinConditionCollection $joinConditions): array
    {
        $result = [];

        while(true) {
            $result[] = $tableAlias;

            if(!$joinConditions->issetByJoinAs($tableAlias)) {
                break;
            }

            $tableAlias = $joinConditions->byJoinAs($tableAlias)->joinTo->alias;
        }

        return array_reverse($result);
    }

    /**
     * @inheritDoc
     * @return Traversable<Table>
     */
    public function getIterator(): Traversable
    {
        foreach($this->mapByAlias as $table) {
            yield $table;
        }
    }

    /**
     * @inheritDoc
     */
    public function count(): int
    {
        return count($this->mapByAlias);
    }

    /**
     * Adds table object to map
     * @param string $mapName name of the property stored the target map
     * @param string $key key to add by
     * @param Table $table table object to add
     * @return $this
     * @throws QueryRelationManagerException
     */
    protected function addToMap(string $mapName, string $key, Table $table): self
    {
        if(isset($this->{$mapName}[$table->{$key}])) {
            throw new QueryRelationManagerException("duplicate key '{$key}' in map '{$mapName}' of TableManager");
        }
        $this->{$mapName}[$table->{$key}] = $table;

        return $this;
    }

    /**
     * Returns table object from map
     * @param string $mapName name of the property stored the source map
     * @param string $key key to get by
     * @return Table
     * @throws QueryRelationManagerException
     */
    protected function getFromMap(string $mapName, string $key): Table
    {
        if(!isset($this->{$mapName}[$key])) {
            throw new QueryRelationManagerException("key '{$key}' not found in map '{$mapName}' of TableManager");
        }

        return $this->{$mapName}[$key];
    }
}
