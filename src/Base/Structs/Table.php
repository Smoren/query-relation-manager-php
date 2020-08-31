<?php


namespace Smoren\Yii2\QueryRelationManager\Base\Structs;


use Smoren\Yii2\QueryRelationManager\Base\QueryRelationManagerException;

/**
 * Class Table
 * Класс-хранилище данных о таблице, которая участвует в запросе
 * @package Smoren\Yii2\QueryRelationManager\Base\Structs
 * @author Smoren <ofigate@gmail.com>
 */
class Table
{
    /**
     * @var string ORM-класс, представляющий таблицу
     */
    public $className;

    /**
     * @var string имя таблицы в БД
     */
    public $name;

    /**
     * @var string псевдоним таблицы в запросе
     */
    public $alias;

    /**
     * @var string[] поля первичного ключа таблицы
     */
    public $primaryKey;

    /**
     * @var string поле-контейнер у родительского элемента, в который будет помещен элемент этой таблицы
     */
    public $containerFieldAlias;

    /**
     * @var array карта полей таблицы ["`псевдонимТаблицы`.`имяПоля`" => "псевдонимТаблицы_имяПоля", ...]
     */
    protected $fieldMap = [];

    /**
     * @var array обратная карта полей таблицы ["псевдонимТаблицы_имяПоля" => "имяПоля"]
     */
    protected $fieldMapReverse = [];

    /**
     * @var array обратная карта полей, составляющих первичный ключ таблицы
     * Пример: ["псевдонимТаблицы_имяПоля" => "имяПоля"]
     */
    protected $pkFieldMapReverse = [];

    /**
     * Table constructor.
     * @param string $className ORM-класс, представляющий таблицу
     * @param string $name имя таблицы в БД
     * @param string $alias псевдоним таблицы в запросе
     * @param array $fields список имен полей таблицы
     * @param array $primaryKey поля первичного ключа таблицы
     * @param string $containerFieldAlias поле-контейнер у родительского элемента, в который будет помещен элемент этой таблицы
     * @throws QueryRelationManagerException
     */
    public function __construct(
        string $className, string $name, string $alias, array $fields, array $primaryKey, ?string $containerFieldAlias = null
    )
    {
        $this->className = $className;
        $this->name = $name;
        $this->alias = $alias;
        $this->primaryKey = $primaryKey;
        $this->containerFieldAlias = $containerFieldAlias;

        $bufMap = [];
        foreach($fields as $field) {
            $bufMap[$field] = "{$this->alias}_{$field}";
            $this->fieldMap["`{$this->alias}`.`{$field}`"] = "{$this->alias}_{$field}";
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
     * Возвращает карту полей таблицы ["`псевдонимТаблицы`.`имяПоля`" => "псевдонимТаблицы_имяПоля", ...]
     * @return string[]
     */
    public function getFieldMap(): array
    {
        return $this->fieldMap;
    }

    /**
     * Возвращает имя поля по его имени с префиксом
     * @param string $fieldPrefixed имя поля с префиксом (псевдонимом таблицы)
     * @return string
     */
    public function getField(string $fieldPrefixed): string
    {
        return $this->fieldMapReverse[$fieldPrefixed];
    }

    /**
     * Возвращает строку, состоящую из полей первичного ключа таблицы, разделенных дефисом
     * @return string
     */
    public function stringifyPrimaryKey(): string
    {
        return implode('-', $this->primaryKey);
    }

    /**
     * Получение данных из кортежа, представляющего из себя строку из результата запроса к БД
     * @param array $row строка из результата запроса SELECT
     * @param JoinConditionCollection $conditionCollection коллекция условий запроса
     * @return array [
     *  "данные из строки, соотвествующие таблице",
     *  "значения полей первичного ключа через дефис",
     *  "псевдоним этой таблицы",
     *  "псевдоним таблицы, к которой осуществляется присоединение",
     *  "значения полей внешнего ключа через дефис",
     *  "поле-контейнер у родительского элемента, в который будет помещен элемент этой таблицы",
     *  "тип отношения к родительской таблице (1 — "один к одному" или 2 — "один ко многим")"
     * ]
     * @throws QueryRelationManagerException
     */
    public function getDataFromRow(array $row, JoinConditionCollection $conditionCollection): array
    {
        $item = [];

        foreach($row as $key => $val) {
            if(isset($this->fieldMapReverse[$key])) {
                $item[$this->fieldMapReverse[$key]] = $val;
            }
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

        try {
            $cond = $conditionCollection->byJoinAs($this->alias);
            $joinTo = $cond->joinTo;
            $aliasTo = $joinTo->alias;
            $foreignKeyValue = $joinTo->stringifyPrimaryKeyValue($row);
            $type = $cond->type;
        } catch(QueryRelationManagerException $e) {
            $aliasTo = null;
            $foreignKeyValue = null;
            $containerFieldAlias = null;
            $type = null;
        }

        return [$item, $primaryKeyValue, $this->alias, $aliasTo, $foreignKeyValue, $this->containerFieldAlias, $type];
    }

    /**
     * Получение списка полей первичного ключа таблицы с префиксом-псевдонимом таблицы через точку
     * @return array
     */
    public function getPrimaryKeyForSelect(): array
    {
        $result = [];
        foreach($this->primaryKey as $field) {
            $result[] = "`{$this->alias}`.`{$field}`";
        }

        return $result;
    }

    /**
     * Получение значений полей первичного ключа таблицы в виде строки через дефис
     * @param array $row строка из результата запроса SELECT
     * @return string
     * @throws QueryRelationManagerException
     */
    protected function stringifyPrimaryKeyValue(array $row): string
    {
        $primaryKeyValues = [];

        foreach($this->pkFieldMapReverse as $fieldPrefixed => $field) {
            if(!isset($row[$fieldPrefixed])) {
                throw new QueryRelationManagerException("no primary key field '{$field}' found in row");
            }
            $primaryKeyValues[] = $row[$fieldPrefixed];
        }

        return implode('-', $primaryKeyValues);
    }
}