<?php


namespace Smoren\Yii2\QueryRelationManager\Base;


use Smoren\Yii2\QueryRelationManager\Base\Structs\JoinCondition;
use Smoren\Yii2\QueryRelationManager\Base\Structs\JoinConditionCollection;
use Smoren\Yii2\QueryRelationManager\Base\Structs\Table;
use Smoren\Yii2\QueryRelationManager\Base\Structs\TableCollection;

/**
 * Абстрактный класс, отвечающий за построение запроса на получение данных из нескольких таблиц
 * с учетом их отношений и дополнительных условий, а также за представление результата в древовидной структуре
 * @package Smoren\Yii2\QueryRelationManager\Base
 * @author Smoren <ofigate@gmail.com>
 */
abstract class QueryRelationManagerBase
{
    /**
     * @var QueryWrapperInterface объект билдера запроса
     */
    protected $query;

    /**
     * @var JoinConditionCollection коллекция отношений в запросе
     */
    protected $joinConditionCollection;

    /**
     * @var TableCollection коллекция таблиц, участвующих в запросе
     */
    protected $tableCollection;

    /**
     * @var callable[] массив функций-коллбэков для внесения дополнительных корректив в запров
     */
    protected $filters = [];

    /**
     * @var callable[] карта функций-коллбэков по псевдониму таблицы для внесения изменений в готовые данные результата
     */
    protected $modifierMap = [];

    /**
     * Начинает формирование данных запроса
     * @param string $className имя класса ActiveRecord, сущности которого нужно получить
     * @param string $tableAlias псевдоним таблицы в БД для записи отношений
     * @return static новый объект relation-мененджера
     * @throws QueryRelationManagerException
     */
    public static function select(string $className, string $tableAlias): self
    {
        return new static($className, $tableAlias);
    }

    /**
     * Добавляет к запросу связь "один к одному" с другой сущностью ActiveRecord
     * @param string $containerFieldAlias название поля, куда будет записана сущность в результате
     * @param string $className имя класса ActiveRecord, сущности которого нужно подключить
     * @param string $joinAs псевдоним для таблицы, связанной с классом
     * @param string $joinTo псевдоним таблицы, к которой будут подключаться сущности класса
     * @param array $joinCondition основное условие присоединения
     * @param string $joinType тип присоединения таблицы ("inner", "left", "right")
     * @param string|null $extraJoinCondition дополнительные условия join-связи
     * @param array $extraJoinParams параметры дополнительных условий join-связи
     * @return $this
     * @throws QueryRelationManagerException
     */
    public function withSingle(
        string $containerFieldAlias, string $className, string $joinAs, string $joinTo,
        array $joinCondition, string $joinType = 'left',
        ?string $extraJoinCondition = null, array $extraJoinParams = []
    ): self
    {
        $table = new Table(
            $className, $this->getTableName($className), $joinAs,
            $this->getTableFields($className), $this->getPrimaryKey($className), $containerFieldAlias
        );

        $this->tableCollection->add($table);

        $this->joinConditionCollection->add(new JoinCondition(
            JoinCondition::TYPE_SINGLE, $table, $this->tableCollection->byAlias($joinTo),
            $joinCondition, $joinType, $extraJoinCondition, $extraJoinParams
        ));

        return $this;
    }

    /**
     * Добавляет к запросу связь "один ко многим" с другими сущностями ActiveRecord
     * @param string $containerFieldAlias название поля, куда будет записана сущность в результате
     * @param string $className имя класса ActiveRecord, сущности которого нужно подключить
     * @param string $joinAs псевдоним для таблицы, связанной с классом
     * @param string $joinTo псевдоним таблицы, к которой будут подключаться сущности класса
     * @param array $joinCondition основное условие присоединения
     * @param string $joinType тип присоединения таблицы ("inner", "left", "right")
     * @param string|null $extraJoinCondition дополнительные условия join-связи
     * @param array $extraJoinParams параметры дополнительных условий join-связи
     * @return $this
     * @throws QueryRelationManagerException
     */
    public function withMultiple(
        string $containerFieldAlias, string $className, string $joinAs, string $joinTo,
        array $joinCondition, string $joinType = 'left',
        ?string $extraJoinCondition = null, array $extraJoinParams = []
    ): self
    {
        $table = new Table(
            $className, $this->getTableName($className), $joinAs,
            $this->getTableFields($className), $this->getPrimaryKey($className), $containerFieldAlias
        );

        $this->tableCollection->add($table);

        $this->joinConditionCollection->add(new JoinCondition(
            JoinCondition::TYPE_MULTIPLE, $table, $this->tableCollection->byAlias($joinTo),
            $joinCondition, $joinType, $extraJoinCondition, $extraJoinParams
        ));

        return $this;
    }

    /**
     * Добавляет функцию-модификатор запроса
     * @param callable $filter
     * @return $this
     */
    public function filter(callable $filter): self
    {
        $this->filters[] = $filter;

        return $this;
    }

    /**
     * Устанавливает для таблицы функцию-модификатор сущности результата
     * @param string $tableAlias псевдоним таблицы
     * @param callable $modifier функция-модификатор результата
     * @return $this
     */
    public function modify(string $tableAlias, callable $modifier): self
    {
        $this->modifierMap[$tableAlias] = $modifier;

        return $this;
    }

    /**
     * Выполняет запрос к базе, собирает и возвращает результат
     * @param mixed|null $db подключение к БД
     * @return array массив сущностей главной таблицы с отношениями подключенных таблиц
     * @throws QueryRelationManagerException
     */
    public function all($db = null): array
    {
        $this->prepare();

        $rows = $this->query->all($db);

        $map = [];
        $this->tableCollection->each(function(Table $table) use (&$map) {
            $map[$table->alias] = [];
        });

        $bufMap = [];

        foreach($rows as $row) {
            $this->tableCollection->each(function(Table $table) use (&$map, &$row, &$bufMap) {
                try {
                    [$item, $pkValue, $alias, $aliasTo, $fkValue, $containerFieldAlias, $type]
                        = $table->getDataFromRow($row, $this->joinConditionCollection);

                    if(!isset($map[$alias][$pkValue])) {
                        $map[$alias][$pkValue] = &$item;
                    }

                    if($aliasTo !== null) {
                        $bufMapKey = implode('-', [$aliasTo, $fkValue, $containerFieldAlias, $pkValue]);
                        switch($type) {
                            case JoinCondition::TYPE_SINGLE:
                                if(!isset($bufMap[$bufMapKey])) {
                                    $map[$aliasTo][$fkValue][$containerFieldAlias] = &$item;
                                    $bufMap[$bufMapKey] = 1;
                                }
                                break;
                            case JoinCondition::TYPE_MULTIPLE:
                                if(!isset($bufMap[$bufMapKey])) {
                                    $map[$aliasTo][$fkValue][$containerFieldAlias][] = &$item;
                                    $bufMap[$bufMapKey] = 1;
                                }
                                break;
                            default:
                                throw new QueryRelationManagerException("unknown condition type '{$type}'");
                        }
                    }

                } catch(QueryRelationManagerException $e) {
                    // пропускаем
                }
            });
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
     * Создает и выстраивает SQL-запрос
     * @return QueryWrapperInterface
     * @throws QueryRelationManagerException
     */
    public function prepare(): QueryWrapperInterface
    {
        $this->query = $this->createQuery();

        $arSelect = [];
        $this->tableCollection->each(function(Table $table) use (&$arSelect) {
            foreach($table->getFieldMap() as $fieldName => $fieldNamePrefixed) {
                $arSelect[$fieldNamePrefixed] = $fieldName;
            }
        });

        $mainTable = $this->tableCollection->getMainTable();

        $this->query
            ->select($arSelect)
            ->from([$mainTable->alias => $mainTable->name]);


        $this->joinConditionCollection->each(function(JoinCondition $cond) {
            $this->query->join(
                $cond->joinType, [$cond->table->alias => $cond->table->name], $cond->stringify(), $cond->extraJoinParams
            );
        });


        foreach($this->filters as $modifier) {
            $modifier($this->query->getQuery());
        }

        return $this->query;
    }

    /**
     * Возвращает коллекцию объектов таблиц, участвующих в запросе
     * @return TableCollection
     */
    public function getTableCollection(): TableCollection
    {
        return $this->tableCollection;
    }

    /**
     * Возвращает текст SQL-запроса
     * @return string текст SQL-запроса
     * @throws QueryRelationManagerException
     */
    public function getRawSql(): string
    {
        $this->prepare();

        return $this->query->getRawSql();
    }

    /**
     * Возвращает имя таблицы по классу сущности ActiveRecord
     * @param string $className имя класса
     * @return string имя таблицы
     * @throws QueryRelationManagerException
     */
    abstract protected function getTableName(string $className): string;

    /**
     * Возвращает список полей таблицы
     * @param string $className
     * @return array
     */
    abstract protected function getTableFields(string $className): array;

    /**
     * Возвращает поля первичного ключа таблицы
     * @param string $className
     * @return array
     */
    abstract protected function getPrimaryKey(string $className): array;

    /**
     * Создает объект запроса
     * @return QueryWrapperInterface
     */
    abstract protected function createQuery(): QueryWrapperInterface;

    /**
     * Магический метод для клонирования инстанса
     */
    public function __clone()
    {
        $this->joinConditionCollection = clone $this->joinConditionCollection;
        $this->tableCollection = clone $this->tableCollection;
    }

    /**
     * QueryRelationManager constructor.
     * @param string $className имя класса сущности ActiveRecord
     * @param string $alias псевдоним таблицы сущности
     * @throws QueryRelationManagerException
     */
    protected function __construct(string $className, string $alias)
    {
        $this->tableCollection = new TableCollection();
        $this->joinConditionCollection = new JoinConditionCollection();

        $this->tableCollection->add(new Table(
            $className, $this->getTableName($className), $alias,
            $this->getTableFields($className), $this->getPrimaryKey($className)
        ));
    }
}