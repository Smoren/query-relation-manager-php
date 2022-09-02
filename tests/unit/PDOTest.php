<?php

namespace Smoren\QueryRelationManager\Tests\Unit;

use Codeception\Lib\Console\Output;
use Smoren\QueryRelationManager\Base\QueryRelationManagerException;
use Smoren\QueryRelationManager\Base\Structs\JoinCondition;
use Smoren\QueryRelationManager\Base\Structs\JoinConditionCollection;
use Smoren\QueryRelationManager\Base\Structs\Table;
use Smoren\QueryRelationManager\Base\Structs\TableCollection;
use Smoren\QueryRelationManager\Pdo\QueryRelationManager;
use Smoren\QueryRelationManager\Pdo\QueryWrapper;

class PDOTest extends \Codeception\Test\Unit
{
    public function testError()
    {
        try {
            QueryRelationManager::select('address', 'a')->all();
            $this->expectError();
        } catch(QueryRelationManagerException $e) {
            $this->assertEquals('no pdo connection opened', $e->getMessage());
        }
    }

    /**
     * @throws QueryRelationManagerException
     */
    public function testAddress()
    {
        $this->initDbConfig();

        $result = QueryRelationManager::select('address', 'a')
            ->withSingle('city', 'city', 'c', 'a', ['id' =>  'city_id'])
            ->withMultiple('places', 'place', 'p', 'a', ['address_id' =>  'id'])
            ->withMultiple('comments', 'comment', 'cm', 'p', ['place_id' =>  'id'])
            ->all();

        $this->assertCount(4, $result);

        $resultMap = $this->indexArray($result, 'id');

        $this->assertEquals('Moscow', $resultMap[1]['city']['name']);
        $this->assertEquals('Moscow', $resultMap[2]['city']['name']);
        $this->assertEquals('St. Petersburg', $resultMap[3]['city']['name']);
        $this->assertEquals('St. Petersburg', $resultMap[4]['city']['name']);

        $this->assertEquals(2, count($resultMap[1]['places']));
        $this->assertEquals(1, count($resultMap[2]['places']));
        $this->assertEquals(2, count($resultMap[3]['places']));
        $this->assertEquals(1, count($resultMap[4]['places']));

        $mapPlaceIdToCommentsCount = [
            1 => 3,
            2 => 0,
            3 => 1,
            4 => 0,
            5 => 1,
            6 => 1,
        ];

        foreach($resultMap as $addressId => &$address) {
            foreach($address['places'] as $place) {
                $this->assertEquals($mapPlaceIdToCommentsCount[$place['id']], count($place['comments']));
            }
        }
        unset($address);
    }

    /**
     * @throws QueryRelationManagerException
     */
    public function testPlace()
    {
        $this->initDbConfig();

        $result = QueryRelationManager::select('place', 'p')
            ->withSingle('address', 'address', 'a', 'p', ['id' => 'address_id'])
            ->withSingle('city', 'city', 'c', 'a', ['id' => 'city_id'])
            ->withMultiple(
                'comments',
                'comment',
                'cm',
                'p',
                ['place_id' => 'id'],
                'inner',
                'and cm.mark >= :mark',
                [':mark' => 3]
            )
            ->modify('p', function(array &$place) {
                $place['comments_count'] = count($place['comments']);
                $place['mark_five_count'] = 0;
                $place['mark_average'] = 0;

                foreach($place['comments'] as $comment) {
                    $place['mark_average'] += $comment['mark'];
                    if($comment['mark'] == 5) {
                        $place['mark_five_count']++;
                    }
                }

                $place['mark_average'] /= $place['comments_count'];
            })
            ->all();

        $this->assertCount(4, $result);

        $resultMap = $this->indexArray($result, 'id');

        $this->assertEquals('Tverskaya st., 7', $resultMap[1]['address']['name']);
        $this->assertEquals('Schipok st., 1', $resultMap[3]['address']['name']);
        $this->assertEquals('Mayakovskogo st., 12', $resultMap[5]['address']['name']);
        $this->assertEquals('Galernaya st., 3', $resultMap[6]['address']['name']);

        $this->assertEquals('Moscow', $resultMap[1]['address']['city']['name']);
        $this->assertEquals('Moscow', $resultMap[3]['address']['city']['name']);
        $this->assertEquals('St. Petersburg', $resultMap[5]['address']['city']['name']);
        $this->assertEquals('St. Petersburg', $resultMap[6]['address']['city']['name']);

        $this->assertCount(2, $resultMap[1]['comments']);
        $this->assertCount(1, $resultMap[3]['comments']);
        $this->assertCount(1, $resultMap[5]['comments']);
        $this->assertCount(1, $resultMap[6]['comments']);

        $this->assertEquals(2, $resultMap[1]['comments_count']);
        $this->assertEquals(1, $resultMap[3]['comments_count']);
        $this->assertEquals(1, $resultMap[5]['comments_count']);
        $this->assertEquals(1, $resultMap[6]['comments_count']);

        $this->assertEquals(1, $resultMap[1]['mark_five_count']);
        $this->assertEquals(1, $resultMap[3]['mark_five_count']);
        $this->assertEquals(0, $resultMap[5]['mark_five_count']);
        $this->assertEquals(0, $resultMap[6]['mark_five_count']);

        $this->assertEquals(4, $resultMap[1]['mark_average']);
        $this->assertEquals(5, $resultMap[3]['mark_average']);
        $this->assertEquals(4, $resultMap[5]['mark_average']);
        $this->assertEquals(3, $resultMap[6]['mark_average']);
    }

    /**
     * @throws QueryRelationManagerException
     */
    public function testQuery()
    {
        $this->initDbConfig();

        $result = QueryRelationManager::select('place', 'p')->filter(function(string &$q) {
            $q .= 'WHERE p.id = 1';
        })->all();
        $this->assertCount(1, $result);

        $sql = QueryRelationManager::select('place', 'p')->getRawSql();
        $this->assertEquals(
            'SELECT p.id AS p_id, p.address_id AS p_address_id, p.name AS p_name  FROM  place p ',
            $sql
        );

        $sql = QueryRelationManager::select('place', 'p')
            ->withSingle('address', 'address', 'a', 'p', ['id' => 'address_id'])
            ->withSingle('city', 'city', 'c', 'a', ['id' => 'city_id'])
            ->withMultiple(
                'comments',
                'comment',
                'cm',
                'p',
                ['place_id' => 'id'],
                'inner',
                'and cm.mark >= :mark',
                [':mark' => 3]
            )
            ->getRawSql();

        $this->assertEquals(
    "SELECT p.id AS p_id, p.address_id AS p_address_id, p.name AS p_name, a.id AS a_id, ".
            "a.city_id AS a_city_id, a.name AS a_name, c.id AS c_id, c.name AS c_name, cm.id AS cm_id, ".
            "cm.place_id AS cm_place_id, cm.username AS cm_username, cm.mark AS cm_mark, cm.text AS cm_text  ".
            "FROM  place p  left JOIN address a  ON a.id = p.address_id   left JOIN city c  ON c.id = a.city_id   ".
            "inner JOIN comment cm  ON cm.place_id = p.id and cm.mark >= '3' ",
            $sql
        );
        $a = 1;
    }

    /**
     * @throws QueryRelationManagerException
     */
    public function testExtra()
    {
        $this->initDbConfig();

        $q = QueryRelationManager::select('place', 'p')
            ->withSingle('address', 'address', 'a', 'p', ['id' => 'address_id'])
            ->withSingle('city', 'city', 'c', 'a', ['id' => 'city_id'])
            ->withMultiple(
                'comments',
                'comment',
                'cm',
                'p',
                ['place_id' => 'id'],
                'inner',
                'and cm.mark >= :mark',
                [':mark' => 3]
            );

        $tableCollection = $q->getTableCollection();
        $this->assertEquals('place', $tableCollection->getMainTable()->name);

        $qClone = clone $q;

        $this->assertTrue($tableCollection === $q->getTableCollection());
        $this->assertFalse($tableCollection === $qClone->getTableCollection());

        try {
            QueryRelationManager::select('place', 'p')
                ->withSingle('address', 'address', 'a', 'p', ['id' => 'address_id'])
                ->withSingle('address', 'address', 'a', 'p', ['id' => 'address_id']);
            $this->expectError();
        } catch(QueryRelationManagerException $e) {
            $this->assertEquals("duplicate key 'alias' in map 'mapByAlias' of TableManager", $e->getMessage());
        }

        try {
            QueryRelationManager::select('place', 'p')
                ->withSingle('address', 'address', 'p', 'p', ['id' => 'address_id']);
            $this->expectError();
        } catch(QueryRelationManagerException $e) {
            $this->assertEquals("duplicate key 'alias' in map 'mapByAlias' of TableManager", $e->getMessage());
        }
    }

    /**
     * @throws QueryRelationManagerException
     */
    public function testJoinCondition()
    {
        $cond = new JoinCondition(
            JoinCondition::TYPE_MULTIPLE,
            new Table('address', 'address', 'a', ['id', 'name'], ['id']),
            new Table('city', 'city', 'c', ['id', 'name'], ['id']),
            ['city_id' => 'id']
        );
        $condCollection = new JoinConditionCollection();
        $this->assertCount(0, $condCollection);
        $condCollection->add($cond);
        $this->assertCount(1, $condCollection);

        try {
            $condCollection->add($cond);
            $this->expectError();
        } catch(QueryRelationManagerException $e) {
            $this->assertCount(1, $condCollection);
            $this->assertEquals("duplicate table alias 'a'", $e->getMessage());
        }
    }

    /**
     * @throws QueryRelationManagerException
     */
    public function testTable()
    {
        try {
            new Table('address', 'address', 'a', ['id', 'name'], ['id1']);
            $this->expectError();
        } catch(QueryRelationManagerException $e) {
            $this->assertEquals("pk field id1 not found in field list", $e->getMessage());
        }

        $table = new Table('address', 'address', 'a', ['id', 'name'], ['id', 'name']);
        $this->assertEquals('id-name', $table->stringifyPrimaryKey());
        $this->assertEquals('name', $table->getField('a_name'));
        $this->assertEquals('id', $table->getField('a_id'));
        $this->assertEquals(['a.id', 'a.name'], $table->getPrimaryKeyForSelect());

        $tableCollection = new TableCollection();
        $this->assertCount(0, $tableCollection);
        try {
            $tableCollection->getMainTable();
            $this->expectError();
        } catch(QueryRelationManagerException $e) {
            $this->assertCount(0, $tableCollection);
            $this->assertEquals('no main table found in TableManager', $e->getMessage());
        }
        $tableCollection->add($table);
        $this->assertCount(1, $tableCollection);
        $this->assertTrue($table === $tableCollection->getMainTable());

        try {
            $tableCollection->byAlias('nnn');
            $this->expectError();
        } catch(QueryRelationManagerException $e) {
            $this->assertEquals("key 'nnn' not found in map 'mapByAlias' of TableManager", $e->getMessage());
        }
    }

    /**
     * Вывод сообщения на экран
     * @param mixed $log
     */
    public function log($log)
    {
        $output = new Output([]);
        $output->writeln(PHP_EOL);
        $output->writeln('-------log---------');
        $output->writeln(print_r($log, 1));
        $output->writeln('-------log---------');
    }

    /**
     * @param array $input
     * @param string $column
     * @return array
     */
    protected function indexArray(array $input, string $column): array
    {
        $result = [];

        foreach($input as $item) {
            $result[$item[$column]] = $item;
        }

        return $result;
    }

    protected function initDbConfig(): void
    {
        QueryWrapper::setDbConfig('mysql:host=127.0.0.1;dbname=app', 'user', '123456789');
    }
}
