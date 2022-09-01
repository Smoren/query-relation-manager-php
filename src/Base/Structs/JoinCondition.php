<?php

namespace Smoren\QueryRelationManager\Base\Structs;

/**
 * Class JoinCondition
 * Класс-хранилище данных join-отношения
 * @author Smoren <ofigate@gmail.com>
 */
class JoinCondition
{
    /**
     * Отношение "один к одному"
     */
    public const TYPE_SINGLE = 1;

    /**
     * Отношение "один ко многим"
     */
    public const TYPE_MULTIPLE = 2;

    /**
     * @var int тип отношения (1 — "один к одному" или 2 — "один ко многим")
     */
    public int $type;

    /**
     * @var Table присоединяемая таблица
     */
    public Table $table;

    /**
     * @var Table таблица, к которой осуществляется присоединение
     */
    public Table $joinTo;

    /**
     * @var array<string, string> основное условие присоединения
     * ["поле присоединяемой таблицы" => "поле таблицы, к которой осуществляется присоединение", ...]
     */
    public array $joinCondition;

    /**
     * @var string способ присоединения ("inner", "left", "right")
     */
    public string $joinType;

    /**
     * @var string|null дополнительное условие присоединения
     * Например: "and some_field = :some_value"
     */
    public ?string $extraJoinCondition;

    /**
     * @var array<string, scalar> значения параметров дополнительного условия присоединения
     * Например: [":some_value" => "123", ...]
     */
    public array $extraJoinParams;

    /**
     * JoinCondition constructor.
     * @param int $type тип отношения (1 — "один к одному" или 2 — "один ко многим")
     * @param Table $table таблица, к которой осуществляется присоединение
     * @param Table $joinTo таблица, к которой осуществляется присоединение
     * @param array<string, string> $joinCondition основное условие присоединения
     * @param string $joinType способ присоединения ("inner", "left", "right")
     * @param string|null $extraJoinCondition string дополнительное условие присоединения
     * @param array<string, scalar> $extraJoinParams значения параметров дополнительного условия присоединения
     */
    public function __construct(
        int $type,
        Table $table,
        Table $joinTo,
        array $joinCondition,
        string $joinType = 'left',
        ?string $extraJoinCondition = null,
        array $extraJoinParams = []
    ) {
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
