<?php

namespace Smoren\QueryRelationManager\Base;

/**
 * Interface of the wrapper of the ORM query class
 * @author Smoren <ofigate@gmail.com>
 */
interface QueryWrapperInterface
{
    /**
     * Transfers selecting fields to the to ORM query object
     * @param array<string|string> $arSelect selecting fields map ["fieldAlias" => "table.fieldName", ...]
     * @return QueryWrapperInterface
     */
    public function select(array $arSelect): self;

    /**
     * Transfers the "from" table name to ORM query object
     * Передача в запрос таблицы, из которой будет осуществляться выброка
     * @param array<string, string> $mapFrom имя и псевдоним таблицы ["tableAlias" => "tableName"]
     * @return $this
     */
    public function from(array $mapFrom): self;

    /**
     * Transfers data of table join to ORM query object
     * @param string $type join type ("inner", "left", "right")
     * @param array<string, string> $mapTable table alias and name as map ["tableAlias" => "tableName"]
     * @param string $condition join conditions
     * @param array<string, scalar> $extraJoinParams values of dynamic params of join condition
     * @return $this
     */
    public function join(string $type, array $mapTable, string $condition, array $extraJoinParams = []): self;

    /**
     * Returns list of select-query result
     * @param mixed|null $db DB connection object
     * @return array<array<mixed>>
     */
    public function all($db = null): array;

    /**
     * Returns ORM query object
     * @return mixed
     */
    public function getQuery();

    /**
     * Returns raw sql query string
     * @return string
     */
    public function getRawSql(): string;
}
