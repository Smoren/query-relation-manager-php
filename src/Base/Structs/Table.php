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
     * @var string ORM-класс, представляющий таблицу
     */
    public string $className;

    /**
     * @var string имя таблицы в БД
     */
    public string $name;

    /**
     * @var string псевдоним таблицы в запросе
     */
    public string $alias;

    /**
     * @var string[] поля первичного ключа таблицы
     */
    public array $primaryKey;

    /**
     * @var string|null поле-контейнер у родительского элемента, в который будет помещен элемент этой таблицы
     */
    public ?string $containerFieldAlias;

    /**
     * @var array<string, string> карта полей таблицы ["псевдонимТаблицы.имяПоля" => "псевдонимТаблицы_имяПоля", ...]
     */
    protected array $fieldMap = [];

    /**
     * @var array<string, string> обратная карта полей таблицы ["псевдонимТаблицы_имяПоля" => "имяПоля"]
     */
    protected array $fieldMapReverse = [];

    /**
     * @var array<string, string> обратная карта полей, составляющих первичный ключ таблицы
     * Пример: ["псевдонимТаблицы_имяПоля" => "имяПоля"]
     */
    protected array $pkFieldMapReverse = [];

    /**
     * @var array<string> цепочка полей первичных ключей подключаемых таблиц до данной
     */
    protected array $pkFieldChain = [];

    /**
     * Table constructor.
     * @param string $className ORM-класс, представляющий таблицу
     * @param string $name имя таблицы в БД
     * @param string $alias псевдоним таблицы в запросе
     * @param array<string> $fields список имен полей таблицы
     * @param array<string> $primaryKey поля первичного ключа таблицы
     * @param string|null $containerFieldAlias поле-контейнер у родительского элемента,
     * в который будет помещен элемент этой таблицы
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
     * Возвращает карту полей таблицы ["псевдонимТаблицы.имяПоля" => "псевдонимТаблицы_имяПоля", ...]
     * @return array<string>
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
     * Проверка наличия данных из таблицы в кортеже, представляющем из себя строку из результата запроса к БД
     * @param array<string, mixed> $row строка из результата запроса SELECT
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
     * Получение данных из кортежа, представляющего из себя строку из результата запроса к БД
     * @param array<string, mixed> $row строка из результата запроса SELECT
     * @param JoinConditionCollection $conditionCollection коллекция условий запроса
     * @return array<mixed> [
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
     * Получение списка полей первичного ключа таблицы с префиксом-псевдонимом таблицы через точку
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
     * Установка цепочки полей первичных ключей присоединяемых таблиц до данной
     * @param array<string> $pkFieldChain
     * @return $this
     */
    public function setPkFieldChain(array $pkFieldChain): self
    {
        $this->pkFieldChain = $pkFieldChain;
        return $this;
    }

    /**
     * Получение значений полей первичного ключа таблицы в виде строки через дефис
     * @param array<string, mixed> $row строка из результата запроса SELECT
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
