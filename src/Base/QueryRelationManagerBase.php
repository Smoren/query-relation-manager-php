<?php

namespace Smoren\QueryRelationManager\Base;

use Smoren\QueryRelationManager\Base\Structs\JoinCondition;
use Smoren\QueryRelationManager\Base\Structs\JoinConditionCollection;
use Smoren\QueryRelationManager\Base\Structs\Table;
use Smoren\QueryRelationManager\Base\Structs\TableCollection;

/**
 * Base class for making select-query for getting data from several referenced tables and parsing result to the tree
 * @author Smoren <ofigate@gmail.com>
 */
abstract class QueryRelationManagerBase
{
    /**
     * @var QueryWrapperInterface query builder wrapper
     */
    protected QueryWrapperInterface $query;

    /**
     * @var JoinConditionCollection collection of join conditions used in query
     */
    protected JoinConditionCollection $joinConditionCollection;

    /**
     * @var TableCollection collection of tables used in query
     */
    protected TableCollection $tableCollection;

    /**
     * @var array<callable> list of closures for query modifying
     */
    protected array $filters = [];

    /**
     * @var array<string, callable> list of closures indexed by table alias for it's parts of result tree modifying
     */
    protected array $modifierMap = [];

    /**
     * Starts the query
     * @param string $className ORM-model class name to use in "from" section of select-query
     * @param string $tableAlias table alias
     * @return static new instance of relation manager
     * @throws QueryRelationManagerException
     */
    public static function select(string $className, string $tableAlias): self
    {
        return new static($className, $tableAlias);
    }

    /**
     * Adds "join" reference of type "one to one" with another ORM-model
     * @param string $containerFieldAlias container key of parent item to put the current item to
     * @param string $className name of the ORM-model class to use in "join" section
     * @param string $joinAs alias of joined table
     * @param string $joinTo alias of table to join to
     * @param array<string, string> $joinCondition main join condition
     * @param string $joinType join type ("inner", "left", "right")
     * @param string|null $extraJoinCondition extra join conditions
     * @param array<string, scalar> $extraJoinParams values of dynamic extra params
     * @return $this
     * @throws QueryRelationManagerException
     */
    public function withSingle(
        string $containerFieldAlias,
        string $className,
        string $joinAs,
        string $joinTo,
        array $joinCondition,
        string $joinType = 'left',
        ?string $extraJoinCondition = null,
        array $extraJoinParams = []
    ): self {
        $table = new Table(
            $className,
            $this->getTableName($className),
            $joinAs,
            $this->getTableFields($className),
            $this->getPrimaryKey($className),
            $containerFieldAlias
        );

        $this->tableCollection->add($table);

        $this->joinConditionCollection->add(new JoinCondition(
            JoinCondition::TYPE_SINGLE,
            $table,
            $this->tableCollection->byAlias($joinTo),
            $joinCondition,
            $joinType,
            $extraJoinCondition,
            $extraJoinParams
        ));

        return $this;
    }

    /**
     * Adds "join" reference of type "one to many" with another ORM-model
     * @param string $containerFieldAlias container key of parent item to put the current item to
     * @param string $className name of the ORM-model class to use in "join" section
     * @param string $joinAs alias of joined table
     * @param string $joinTo alias of table to join to
     * @param array<string, string> $joinCondition main join condition
     * @param string $joinType join type ("inner", "left", "right")
     * @param string|null $extraJoinCondition extra join conditions
     * @param array<string, scalar> $extraJoinParams values of dynamic extra params
     * @return $this
     * @throws QueryRelationManagerException
     */
    public function withMultiple(
        string $containerFieldAlias,
        string $className,
        string $joinAs,
        string $joinTo,
        array $joinCondition,
        string $joinType = 'left',
        ?string $extraJoinCondition = null,
        array $extraJoinParams = []
    ): self {
        $table = new Table(
            $className,
            $this->getTableName($className),
            $joinAs,
            $this->getTableFields($className),
            $this->getPrimaryKey($className),
            $containerFieldAlias
        );

        $this->tableCollection->add($table);

        $this->joinConditionCollection->add(new JoinCondition(
            JoinCondition::TYPE_MULTIPLE,
            $table,
            $this->tableCollection->byAlias($joinTo),
            $joinCondition,
            $joinType,
            $extraJoinCondition,
            $extraJoinParams
        ));

        return $this;
    }

    /**
     * Adds closure for query modifying
     * @param callable $filter query modifier closure
     * @return $this
     */
    public function filter(callable $filter): self
    {
        $this->filters[] = $filter;
        return $this;
    }

    /**
     * Adds closure for result tree modifying by table alias
     * @param string $tableAlias table alias
     * @param callable $modifier result modifier closure
     * @return $this
     */
    public function modify(string $tableAlias, callable $modifier): self
    {
        $this->modifierMap[$tableAlias] = $modifier;
        return $this;
    }

    /**
     * Executes query, builds tree and return it
     * @param mixed|null $db DB connection object
     * @return array<array<mixed>> result tree
     * @throws QueryRelationManagerException
     */
    public function all($db = null): array
    {
        $this->prepare();

        $rows = $this->query->all($db);

        $map = [];
        foreach($this->tableCollection as $table) {
            $map[$table->alias] = [];
        }

        $bufMap = [];

        foreach($rows as $row) {
            foreach($this->tableCollection as $table) {
                if(!$table->issetDataInRow($row)) {
                    continue;
                }

                [$item, $pkValue, $alias, $aliasTo, $fkValue, $containerFieldAlias, $type]
                    = $table->getDataFromRow($row, $this->joinConditionCollection);

                if(!isset($map[$alias][$pkValue])) {
                    $map[$alias][$pkValue] = &$item;
                }

                if($aliasTo !== null) {
                    $bufMapKey = implode('-', [$aliasTo, $fkValue, $containerFieldAlias, $pkValue]);
                    /** @var string $type */
                    switch($type) {
                        case JoinCondition::TYPE_SINGLE:
                            if(!isset($bufMap[$bufMapKey])) {
                                /** @var mixed[][][] $map */
                                $map[$aliasTo][$fkValue][$containerFieldAlias] = &$item;
                                $bufMap[$bufMapKey] = 1;
                            }
                            break;
                        case JoinCondition::TYPE_MULTIPLE:
                            if(!isset($bufMap[$bufMapKey])) {
                                /** @var mixed[][][][] $map */
                                $map[$aliasTo][$fkValue][$containerFieldAlias][] = &$item;
                                $bufMap[$bufMapKey] = 1;
                            }
                            break;
                        default:
                            throw new QueryRelationManagerException("unknown condition type '{$type}'");
                    }
                }
                unset($item);
            }
        }

        foreach($this->modifierMap as $alias => $modifier) {
            foreach($map[$alias] as $pk => &$item) {
                ($modifier)($item);
            }
            unset($item);
        }

        return array_values($map[$this->tableCollection->getMainTable()->alias]);
    }

    /**
     * Builds query
     * @return QueryWrapperInterface
     * @throws QueryRelationManagerException
     */
    public function prepare(): QueryWrapperInterface
    {
        foreach($this->tableCollection as $table) {
            $table->setPkFieldChain(
                $this->tableCollection->getPkFieldChain($table->alias, $this->joinConditionCollection)
            );
        }

        $this->query = $this->createQuery();

        $arSelect = [];
        foreach($this->tableCollection as $table) {
            foreach($table->getFieldMap() as $fieldName => $fieldNamePrefixed) {
                $arSelect[$fieldNamePrefixed] = $fieldName;
            }
        }

        $mainTable = $this->tableCollection->getMainTable();

        $this->query
            ->select($arSelect)
            ->from([$mainTable->alias => $mainTable->name]);

        foreach($this->joinConditionCollection as $cond) {
            $this->query->join(
                $cond->joinType,
                [$cond->table->alias => $cond->table->name],
                $cond->stringify(),
                $cond->extraJoinParams
            );
        }

        foreach($this->filters as $modifier) {
            $modifier($this->query->getQuery());
        }

        return $this->query;
    }

    /**
     * Returns collection of tables used in query
     * Возвращает коллекцию объектов таблиц, участвующих в запросе
     * @return TableCollection
     */
    public function getTableCollection(): TableCollection
    {
        return $this->tableCollection;
    }

    /**
     * Returns raw SQL query string
     * @return string текст SQL-запроса
     * @throws QueryRelationManagerException
     */
    public function getRawSql(): string
    {
        $this->prepare();

        return $this->query->getRawSql();
    }

    /**
     * Return table name by ORM-model class name
     * @param string $className ORM-model class name
     * @return string table name
     * @throws QueryRelationManagerException
     */
    abstract protected function getTableName(string $className): string;

    /**
     * Returns list of names of the table fields by ORM-model class name
     * @param string $className ORM-model class name
     * @return array<string>
     */
    abstract protected function getTableFields(string $className): array;

    /**
     * Returns list of names of the primary key fields by ORM-model class name
     * @param string $className ORM-model class name
     * @return array<string>
     */
    abstract protected function getPrimaryKey(string $className): array;

    /**
     * Creates ORM query wrapper object
     * @return QueryWrapperInterface
     */
    abstract protected function createQuery(): QueryWrapperInterface;

    /**
     * Magic method for cloning
     */
    public function __clone()
    {
        $this->joinConditionCollection = clone $this->joinConditionCollection;
        $this->tableCollection = clone $this->tableCollection;
    }

    /**
     * QueryRelationManager constructor.
     * @param string $className ORM-model class name
     * @param string $alias DB table alias
     * @throws QueryRelationManagerException
     */
    final protected function __construct(string $className, string $alias)
    {
        $this->tableCollection = new TableCollection();
        $this->joinConditionCollection = new JoinConditionCollection();

        $this->tableCollection->add(new Table(
            $className,
            $this->getTableName($className),
            $alias,
            $this->getTableFields($className),
            $this->getPrimaryKey($className)
        ));
    }
}
