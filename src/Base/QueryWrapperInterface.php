<?php


namespace Smoren\Yii2\QueryRelationManager\Base;


/**
 * Интерфейс для обертки ORM-класса запроса
 * @package Smoren\Yii2\QueryRelationManager\Base
 * @author Smoren <ofigate@gmail.com>
 */
interface QueryWrapperInterface
{
    /**
     * Передача в запрос список выбираемых полей
     * @param array $arSelect карта выбираемых полей ["fieldAlias" => "table.fieldName", ...]
     * @return QueryWrapperInterface
     */
    public function select(array $arSelect): self;

    /**
     * Передача в запрос таблицы, из которой будет осуществляться выброка
     * @param array $mapFrom имя и псевдоним таблицы ["tableAlias" => "tableName"]
     * @return $this
     */
    public function from(array $mapFrom): self;

    /**
     * @param string $type тип присоединения ("inner", "left", "right")
     * @param array $mapTable имя и псевдоним присоединяемой таблицы ["tableAlias" => "tableName"]
     * @param string $condition условия присоединения
     * @param array $extraJoinParams динамические параметры присоединения
     * @return $this
     */
    public function join(string $type, array $mapTable, string $condition, array $extraJoinParams = []): self;

    /**
     * Получение массива строк — результата запроса
     * @param null $db объект подключения к БД
     * @return array
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