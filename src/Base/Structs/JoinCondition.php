<?php


namespace Smoren\Yii2\QueryRelationManager\Base\Structs;

/**
 * Class JoinCondition
 * Класс-хранилище данных join-отношения
 * @package Smoren\Yii2\QueryRelationManager\Base\Structs
 * @author Smoren <ofigate@gmail.com>
 */
class JoinCondition
{
    /**
     * Отношение "один к одному"
     */
    const TYPE_SINGLE = 1;

    /**
     * Отношение "один ко многим"
     */
    const TYPE_MULTIPLE = 2;

    /**
     * @var int тип отношения (1 — "один к одному" или 2 — "один ко многим")
     */
    public $type;

    /**
     * @var Table присоединяемая таблица
     */
    public $table;

    /**
     * @var Table таблица, к которой осуществляется присоединение
     */
    public $joinTo;

    /**
     * @var array основное условие присоединения
     * ["поле присоединяемой таблицы" => "поле таблицы, к которой осуществляется присоединение", ...]
     */
    public $joinCondition;

    /**
     * @var string способ присоединения ("inner", "left", "right")
     */
    public $joinType;

    /**
     * @var string дополнительное условие присоединения
     * Например: "and some_field = :some_value"
     */
    public $extraJoinCondition;

    /**
     * @var array значения параметров дополнительного условия присоединения
     * Например: [":some_value" => "123", ...]
     */
    public $extraJoinParams;

    /**
     * JoinCondition constructor.
     * @param int $type тип отношения (1 — "один к одному" или 2 — "один ко многим")
     * @param Table $table таблица, к которой осуществляется присоединение
     * @param Table $joinTo таблица, к которой осуществляется присоединение
     * @param array $joinCondition основное условие присоединения
     * @param string $joinType способ присоединения ("inner", "left", "right")
     * @param string|null $extraJoinCondition string дополнительное условие присоединения
     * @param array $extraJoinParams значения параметров дополнительного условия присоединения
     */
    public function __construct(
        int $type, Table $table, Table $joinTo, array $joinCondition,
        string $joinType = 'left', ?string $extraJoinCondition = null, array $extraJoinParams = []
    )
    {
        $this->type = $type;
        $this->table = $table;
        $this->joinTo = $joinTo;
        $this->joinCondition = $joinCondition;
        $this->joinType = $joinType;
        $this->extraJoinCondition = $extraJoinCondition;
        $this->extraJoinParams = $extraJoinParams;
    }

    /**
     * Возвращает часть SQL-запроса, содержащую условия присоединения таблицы
     * @return string
     */
    public function stringify(): string
    {
        $joins = [];
        foreach($this->joinCondition as $linkBy => $linkTo) {
            $joins[] = "{$this->table->alias}.{$linkBy} = {$this->joinTo->alias}.{$linkTo}";
        }

        return implode(' AND ', $joins).' '.$this->extraJoinCondition;
    }
}