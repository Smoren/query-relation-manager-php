<?php


namespace Smoren\Yii2\QueryRelationManager\Base\Structs;


use Smoren\Yii2\QueryRelationManager\Base\QueryRelationManagerException;

/**
 * Class TableCollection
 * Класс-коллекция объектов таблиц, участвующих в запросе
 * @package Smoren\Yii2\QueryRelationManager\Base\Structs
 * @author Smoren <ofigate@gmail.com>
 */
class TableCollection
{
    /**
     * @var Table объект главной таблицы запроса
     */
    protected $mainTable;

    /**
     * @var Table[] карта объектов таблиц по имени класса ORM-модели
     */
    protected $mapByClassName = [];

    /**
     * @var Table[] карта объектов таблиц по имени таблицы
     */
    protected $mapByName = [];

    /**
     * @var Table[] карта объектов таблиц по псевдониму таблицы в запросе
     */
    protected $mapByAlias = [];

    /**
     * Добавление объекта таблицы в коллецию
     * @param Table $table таблица, участвующая в запросе
     * @return $this
     * @throws QueryRelationManagerException
     */
    public function add(Table $table): self
    {
        if($this->mainTable === null) {
            $this->mainTable = $table;
        }

        $this->addToMap('mapByClassName', 'className', $table);
        $this->addToMap('mapByName', 'name', $table);
        $this->addToMap('mapByAlias', 'alias', $table);

        return $this;
    }

    /**
     * Получение объекта главной таблицы запроса
     * @return Table
     * @throws QueryRelationManagerException
     */
    public function getMainTable(): Table
    {
        if($this->mainTable === null) {
            throw new QueryRelationManagerException('no main table found in TableManager');
        }

        return $this->mainTable;
    }

    /**
     * Получение объекта таблицы по имени класса ее ORM-модели
     * @param string $className имя класса ORM-модели таблицы
     * @return Table
     * @throws QueryRelationManagerException
     */
    public function byClassName(string $className): Table
    {
        return $this->getFromMap('mapByClassName', $className);
    }

    /**
     * Получение объекта таблицы по ее имени в БД
     * @param string $name имя таблицы в БД
     * @return Table
     * @throws QueryRelationManagerException
     */
    public function byName(string $name): Table
    {
        return $this->getFromMap('mapByName', $name);
    }

    /**
     * Получение объекта таблицы по ее псевдониму в запросе
     * @param string $alias псевдоним таблицы в запросе
     * @return Table
     * @throws QueryRelationManagerException
     */
    public function byAlias(string $alias): Table
    {
        return $this->getFromMap('mapByAlias', $alias);
    }

    /**
     * Перебор коллекции
     * @param callable $callback функция, которая будет запущена для каждого элемента коллекции с передачей оного в качестве аргумента
     * @return $this
     */
    public function each(callable $callback): self
    {
        foreach($this->mapByAlias as $table) {
            $callback($table);
        }
        return $this;
    }

    /**
     * Добавление объекта таблицы в карту
     * @param string $mapName имя члена класса-карты
     * @param string $key ключ в карте
     * @param Table $table объект таблицы
     * @return $this
     * @throws QueryRelationManagerException
     */
    protected function addToMap(string $mapName, string $key, Table $table): self
    {
        if(isset($this->{$mapName}[$table->{$key}])) {
            throw new QueryRelationManagerException("duplicate key '{$key}' in map '{$mapName}' of TableManager");
        }
        $this->{$mapName}[$table->{$key}] = $table;

        return $this;
    }

    /**
     * Получение объекта таблицы из карты
     * @param string $mapName имя члена класса-карты
     * @param string $key ключ в карте
     * @return Table
     * @throws QueryRelationManagerException
     */
    protected function getFromMap(string $mapName, string $key): Table
    {
        if(!isset($this->{$mapName}[$key])) {
            throw new QueryRelationManagerException("key '{$key}' not found in map '{$mapName}' of TableManager");
        }

        return $this->{$mapName}[$key];
    }
}