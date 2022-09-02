<?php

namespace Smoren\QueryRelationManager\Base\Structs;

use Countable;
use IteratorAggregate;
use Smoren\QueryRelationManager\Base\QueryRelationManagerException;
use Traversable;

/**
 * Class JoinConditionManager
 * Класс-коллекция объектов условий присоединения
 * @author Smoren <ofigate@gmail.com>
 * @implements IteratorAggregate<JoinCondition>
 */
class JoinConditionCollection implements Countable, IteratorAggregate
{
    /**
     * @var JoinCondition[] карта объектов условий присоединения по псевдониму присоединяемой таблицы
     */
    protected array $mapByJoinAs = [];

    /**
     * @var JoinCondition[][] карта списка объектов условий присоединения по псевдониму таблицы,
     * к которой осуществляется присоединение
     */
    protected array $matrixByJoinTo = [];

    /**
     * Добавление объекта условия присоединения таблицы
     * @param JoinCondition $condition условие присоединения таблицы
     * @return $this
     * @throws QueryRelationManagerException
     */
    public function add(JoinCondition $condition): self
    {
        if(isset($this->mapByJoinAs[$condition->table->alias])) {
            throw new QueryRelationManagerException("duplicate table alias '{$condition->table->alias}'");
        }
        $this->mapByJoinAs[$condition->table->alias] = $condition;

        if(!isset($this->matrixByJoinTo[$condition->joinTo->alias])) {
            $this->matrixByJoinTo[$condition->joinTo->alias] = [];
        }
        if(isset($this->matrixByJoinTo[$condition->joinTo->alias][$condition->table->alias])) {
            throw new QueryRelationManagerException("duplicate table alias '{$condition->table->alias}'");
        }
        $this->matrixByJoinTo[$condition->joinTo->alias][$condition->table->alias] = $condition;

        return $this;
    }

    /**
     * Проверка наличия объекта условия присоединения таблицы по ее псевдониму в запросе
     * @param string $joinAs псевдоним присоединяемой таблицы
     * @return bool
     */
    public function issetByJoinAs(string $joinAs): bool
    {
        if(!isset($this->mapByJoinAs[$joinAs])) {
            return false;
        }

        return true;
    }

    /**
     * Получение объекта условия присоединения таблицы по ее псевдониму в запросе
     * @param string $joinAs псевдоним присоединяемой таблицы
     * @return JoinCondition
     */
    public function byJoinAs(string $joinAs): JoinCondition
    {
        return $this->mapByJoinAs[$joinAs];
    }

    /**
     * Получение списка объекта условий присоединения таблиц по псевдониму таблицы,
     * к которой осуществляется присоединение
     * @param string $joinTo псевдоним таблицы, к которой осуществляется присоединение
     * @return JoinCondition[]
     */
    public function byJoinTo(string $joinTo): array
    {
        if(!isset($this->matrixByJoinTo[$joinTo])) {
            return [];
        }
        return $this->matrixByJoinTo[$joinTo];
    }

    /**
     * @inheritDoc
     * @return Traversable<JoinCondition>
     */
    public function getIterator(): Traversable
    {
        foreach($this->mapByJoinAs as $condition) {
            yield $condition;
        }
    }

    /**
     * @inheritDoc
     */
    public function count(): int
    {
        return count($this->mapByJoinAs);
    }
}
