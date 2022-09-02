<?php

namespace Smoren\QueryRelationManager\Base\Structs;

use Smoren\QueryRelationManager\Base\QueryRelationManagerException;

/**
 * Class Table
 * Класс-хранилище данных о таблице, которая участвует в запросе
 * @author Smoren <ofigate@gmail.com>
 */
class Table
{
    /**
     * @var string class of ORM model wrapping the table
     */
    public string $className;

    /**
     * @var string table name in DB
     */
    public string $name;

    /**
     * @var string table alias in query
     */
    public string $alias;

    /**
     * @var string[] fields of the primary key of the table
     */
    public array $primaryKey;

    /**
     * @var string|null container key of parent item to put the current item to
     */
    public ?string $containerFieldAlias;

    /**
     * @var array<string, string> table fields map ["tableAlias.fieldName" => "tableAlias_fieldName", ...]
     */
    protected array $fieldMap = [];

    /**
     * @var array<string, string> reverse table fields map ["tableAlias_fieldName" => "tableAlias.fieldName"]
     */
    protected array $fieldMapReverse = [];

    /**
     * @var array<string, string> reverse map of the fields of the primary key
     * e.g. ["tableAlias_fieldName" => "fieldName"]
     */
    protected array $pkFieldMapReverse = [];

    /**
     * @var array<string> chain of the primary key's fields of all the joined tables till current
     */
    protected array $pkFieldChain = [];

    /**
     * Table constructor.
     * @param string $className class of ORM model wrapping the table
     * @param string $name table name in DB
     * @param string $alias table alias in query
     * @param array<string> $fields list of fields of the table
     * @param array<string> $primaryKey fields of the primary key of the table
     * @param string|null $containerFieldAlias container key of parent item to put the current item to
     * @throws QueryRelationManagerException
     */
    public function __construct(
        string $className,
        string $name,
        string $alias,
        array $fields,
        array $primaryKey,
        ?string $containerFieldAlias = null
    ) {
        $this->className = $className;
        $this->name = $name;
        $this->alias = $alias;
        $this->primaryKey = $primaryKey;
        $this->containerFieldAlias = $containerFieldAlias;

        $bufMap = [];
        foreach($fields as $field) {
            $bufMap[$field] = "{$this->alias}_{$field}";
            $this->fieldMap["{$this->alias}.{$field}"] = "{$this->alias}_{$field}";
            $this->fieldMapReverse["{$this->alias}_{$field}"] = $field;
        }

        foreach($this->primaryKey as $field) {
            if(!isset($bufMap[$field])) {
                throw new QueryRelationManagerException("pk field {$field} not found in field list");
            }
            $this->pkFieldMapReverse[$bufMap[$field]] = $field;
        }
    }

    /**
     * Returns fields map of the table ["tableAlias.fieldName" => "tableAlias_fieldName", ...]
     * @return array<string>
     */
    public function getFieldMap(): array
    {
        return $this->fieldMap;
    }

    /**
     * Returns field name by it's prefixed name
     * @param string $fieldPrefixed prefixed field name (prefix is table alias)
     * @return string
     */
    public function getField(string $fieldPrefixed): string
    {
        return $this->fieldMapReverse[$fieldPrefixed];
    }

    /**
     * Returns string of primary key fields of the table imploded by '-'
     * @return string
     */
    public function stringifyPrimaryKey(): string
    {
        return implode('-', $this->primaryKey);
    }

    /**
     * Returns true if table row data isset in select-query result row
     * @param array<string, mixed> $row select-query result row
     * @return bool
     */
    public function issetDataInRow(array &$row): bool
    {
        foreach($this->pkFieldMapReverse as $prefixedKey => $key) {
            if($row[$prefixedKey] !== null) {
                return true;
            }
        }
        return false;
    }

    /**
     * Returns data from select-query result row
     * @param array<string, mixed> $row select-query result row
     * @param JoinConditionCollection $conditionCollection collection of join conditions of the select-query
     * @return array<mixed> [
     *  (array) "table row data from select-query result row",
     *  (string) "values of the primary key fields imploded by '-'",
     *  (string) "alias of current table",
     *  (string) "alias of table to join current table to",
     *  (string) "values of the foreign key fields imploded by '-'",
     *  (string) "container key of parent item to put the current item to",
     *  (int) "condition type for parent table (1 — 'one to one' или 2 — 'one to many')"
     * ]
     * @throws QueryRelationManagerException
     */
    public function getDataFromRow(array $row, JoinConditionCollection $conditionCollection): array
    {
        $item = [];

        foreach($this->fieldMapReverse as $fieldPrefixed => $field) {
            $item[$field] = $row[$fieldPrefixed];
        }

        /** @var JoinCondition $cond */
        foreach($conditionCollection->byJoinTo($this->alias) as $cond) {
            switch($cond->type) {
                case JoinCondition::TYPE_MULTIPLE:
                    $item[$cond->table->containerFieldAlias] = [];
                    break;
                case JoinCondition::TYPE_SINGLE:
                    $item[$cond->table->containerFieldAlias] = null;
                    break;
                default:
                    throw new QueryRelationManagerException("unknown condition type '{$cond->type}'");
            }
        }

        $primaryKeyValue = $this->stringifyPrimaryKeyValue($row);

        if($conditionCollection->issetByJoinAs($this->alias)) {
            $cond = $conditionCollection->byJoinAs($this->alias);
            $joinTo = $cond->joinTo;
            $aliasTo = $joinTo->alias;
            $foreignKeyValue = $joinTo->stringifyPrimaryKeyValue($row);
            $type = $cond->type;
        } else {
            $aliasTo = null;
            $foreignKeyValue = null;
            $containerFieldAlias = null;
            $type = null;
        }

        return [$item, $primaryKeyValue, $this->alias, $aliasTo, $foreignKeyValue, $this->containerFieldAlias, $type];
    }

    /**
     * Returns list of primary key field names prefixed by table alias by dot
     * @return array<string>
     */
    public function getPrimaryKeyForSelect(): array
    {
        $result = [];
        foreach($this->primaryKey as $field) {
            $result[] = "{$this->alias}.{$field}";
        }

        return $result;
    }

    /**
     * Setter for chain of the primary key's fields of all the joined tables till current
     * @param array<string> $pkFieldChain primary key fields chain
     * @return $this
     */
    public function setPkFieldChain(array $pkFieldChain): self
    {
        $this->pkFieldChain = $pkFieldChain;
        return $this;
    }

    /**
     * Returns values of the table primary key as a string imploded by '-'
     * @param array<string, mixed> $row select-query result row
     * @return string
     */
    protected function stringifyPrimaryKeyValue(array $row): string
    {
        $primaryKeyValues = [];

        foreach($this->pkFieldChain as $field) {
            $primaryKeyValues[] = $row[$field];
        }

        return implode('-', $primaryKeyValues);
    }
}
