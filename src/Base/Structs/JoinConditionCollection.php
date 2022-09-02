<?php

namespace Smoren\QueryRelationManager\Base\Structs;

use Countable;
use IteratorAggregate;
use Smoren\QueryRelationManager\Base\QueryRelationManagerException;
use Traversable;

/**
 * Collection of join conditions used in select-query
 * @author Smoren <ofigate@gmail.com>
 * @implements IteratorAggregate<JoinCondition>
 */
class JoinConditionCollection implements Countable, IteratorAggregate
{
    /**
     * @var JoinCondition[] map of JoinCondition objects indexed by alias of joined table
     */
    protected array $mapByJoinAs = [];

    /**
     * @var JoinCondition[][] map of JoinCondition objects lists indexed by alias of the table to join to
     */
    protected array $matrixByJoinTo = [];

    /**
     * Adds JoinCondition to collection
     * @param JoinCondition $condition JoinCondition object
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
     * Returns true if condition exists for the joined table alias
     * @param string $joinAs joined table alias
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
     * Returns join condition for the joined table alias
     * @param string $joinAs joined table alias
     * @return JoinCondition
     */
    public function byJoinAs(string $joinAs): JoinCondition
    {
        return $this->mapByJoinAs[$joinAs];
    }

    /**
     * Returns list of join conditions by alias of table to join to
     * @param string $joinTo alias of table to join to
     * @return JoinCondition[]
     */
    public function byJoinTo(string $joinTo): array
    {
        return $this->matrixByJoinTo[$joinTo] ?? [];
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
