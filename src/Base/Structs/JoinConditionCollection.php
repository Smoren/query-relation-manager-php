<?php


namespace Smoren\Yii2\QueryRelationManager\Base\Structs;


use Smoren\Yii2\QueryRelationManager\Base\QueryRelationManagerException;

/**
 * Class JoinConditionManager
 * Класс-коллекция объектов условий присоединения
 * @package Smoren\Yii2\QueryRelationManager\Base\Structs
 * @author Smoren <ofigate@gmail.com>
 */
class JoinConditionCollection
{
    /**
     * @var JoinCondition[] карта объектов условий присоединения по псевдониму присоединяемой таблицы
     */
    protected $mapByJoinAs = [];

    /**
     * @var JoinCondition[][] катра спика объектов условий присоединения по псевдониму таблицы, к которой осуществляется присоединение
     */
    protected $matrixByJoinTo = [];

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
     * Получение объекта условия присоединения таблицы по ее псевдониму в запросе
     * @param string $joinAs псевдоним присоединяемой таблицы
     * @return JoinCondition
     * @throws QueryRelationManagerException
     */
    public function byJoinAs(string $joinAs): JoinCondition
    {
        if(!isset($this->mapByJoinAs[$joinAs])) {
            throw new QueryRelationManagerException("no condition found by table alias '{$joinAs}'");
        }
        return $this->mapByJoinAs[$joinAs];
    }

    /**
     * Получение списка объекта условий присоединения таблиц по псевдониму таблицы, к которой осуществляется присоединение
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
     * Перебор коллекции
     * @param callable $callback функция, которая будет запущена для каждого элемента коллекции с передачей оного в качестве аргумента
     * @return $this
     */
    public function each(callable $callback): self
    {
        foreach($this->mapByJoinAs as $condition) {
            $callback($condition);
        }
        return $this;
    }
}