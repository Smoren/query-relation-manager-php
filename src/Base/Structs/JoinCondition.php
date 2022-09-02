<?php

namespace Smoren\QueryRelationManager\Base\Structs;

/**
 * Class JoinCondition
 * @author Smoren <ofigate@gmail.com>
 */
class JoinCondition
{
    /**
     * Condition type "one to one"
     */
    public const TYPE_SINGLE = 1;

    /**
     * Condition type "one to many"
     */
    public const TYPE_MULTIPLE = 2;

    /**
     * @var int Condition type
     * (1 — "one to one" or 2 — "one to many")
     */
    public int $type;

    /**
     * @var Table joined table
     */
    public Table $table;

    /**
     * @var Table table to join to
     */
    public Table $joinTo;

    /**
     * @var array<string, string> main join condition
     * ["field of joined table" => "field of table to join to", ...]
     */
    public array $joinCondition;

    /**
     * @var string join type ("inner", "left", "right")
     */
    public string $joinType;

    /**
     * @var string|null extra join condition
     * e.g. "and some_field = :some_value"
     */
    public ?string $extraJoinCondition;

    /**
     * @var array<string, scalar> dynamic params values of the extra join condition
     * e.g. [":some_value" => "123", ...]
     */
    public array $extraJoinParams;

    /**
     * JoinCondition constructor.
     * @param int $type condition type (1 — "one to one" или 2 — "one to many")
     * @param Table $table joined table
     * @param Table $joinTo table to join to
     * @param array<string, string> $joinCondition main join condition
     * @param string $joinType join type ("inner", "left", "right")
     * @param string|null $extraJoinCondition string extra join condition (e.g. "and some_field = :some_value")
     * @param array<string, scalar> $extraJoinParams dynamic params values of the extra join condition
     * (e.g. [":some_value" => "123", ...])
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
     * Returns SQL query part which has join conditions of the current table
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
