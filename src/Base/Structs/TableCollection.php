<?php

namespace Smoren\QueryRelationManager\Base\Structs;

use Countable;
use IteratorAggregate;
use Smoren\QueryRelationManager\Base\QueryRelationManagerException;
use Traversable;

/**
 * Class TableCollection
 * Класс-коллекция объектов таблиц, участвующих в запросе
 * @author Smoren <ofigate@gmail.com>
 * @implements IteratorAggregate<Table>
 */
class TableCollection implements Countable, IteratorAggregate
{
    /**
     * @var Table|null объект главной таблицы запроса
     */
    protected ?Table $mainTable = null;

    /**
     * @var Table[] карта объектов таблиц по псевдониму таблицы в запросе
     */
    protected array $mapByAlias = [];

    /**
     * Добавление объекта таблицы в коллецию
     * @param Table $table таблица, участвующая в запросе
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
     * Получение объекта главной таблицы запроса
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
     * Получение объекта таблицы по ее псевдониму в запросе
     * @param string $alias псевдоним таблицы в запросе
     * @return Table
     * @throws QueryRelationManagerException
     */
    public function byAlias(string $alias): Table
    {
        return $this->getFromMap('mapByAlias', $alias);
    }

    /**
     * Получение цепочки первичных ключей присоединяемых таблиц до данной
     * @param string $tableAlias
     * @param JoinConditionCollection $joinConditions
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
     * Получение цепочки псеводнимов присоединяемых таблиц до данной
     * @param string $tableAlias
     * @param JoinConditionCollection $joinConditions
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
     * Добавление объекта таблицы в карту
     * @param string $mapName имя члена класса-карты
     * @param string $key ключ в карте
     * @param Table $table объект таблицы
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
     * Получение объекта таблицы из карты
     * @param string $mapName имя члена класса-карты
     * @param string $key ключ в карте
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
