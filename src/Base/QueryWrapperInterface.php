<?php

namespace Smoren\QueryRelationManager\Base;

/**
 * Интерфейс для обертки ORM-класса запроса
 * @author Smoren <ofigate@gmail.com>
 */
interface QueryWrapperInterface
{
    /**
     * Передача в запрос список выбираемых полей
     * @param array<string|string> $arSelect карта выбираемых полей ["fieldAlias" => "table.fieldName", ...]
     * @return QueryWrapperInterface
     */
    public function select(array $arSelect): self;

    /**
     * Передача в запрос таблицы, из которой будет осуществляться выброка
     * @param array<string, string> $mapFrom имя и псевдоним таблицы ["tableAlias" => "tableName"]
     * @return $this
     */
    public function from(array $mapFrom): self;

    /**
     * @param string $type тип присоединения ("inner", "left", "right")
     * @param array<string, string> $mapTable имя и псевдоним присоединяемой таблицы ["tableAlias" => "tableName"]
     * @param string $condition условия присоединения
     * @param array<string, scalar> $extraJoinParams динамические параметры присоединения
     * @return $this
     */
    public function join(string $type, array $mapTable, string $condition, array $extraJoinParams = []): self;

    /**
     * Получение массива строк — результата запроса
     * @param mixed|null $db объект подключения к БД
     * @return array<array<mixed>>
     */
    public function all($db = null): array;

    /**
     * Получения объекта запроса
     * @return mixed
     */
    public function getQuery();

    /**
     * Получение SQL-кода запроса
     * @return string
     */
    public function getRawSql(): string;
}
