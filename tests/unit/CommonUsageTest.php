<?php

namespace Smoren\QueryRelationManager\Tests\Unit;

use Codeception\Lib\Console\Output;
use Smoren\QueryRelationManager\Base\QueryRelationManagerException;
use Smoren\QueryRelationManager\Pdo\QueryRelationManager;
use Smoren\QueryRelationManager\Pdo\QueryWrapper;

class CommonUsageTest extends \Codeception\Test\Unit
{
    /**
     * @throws QueryRelationManagerException
     */
    public function testAddress()
    {
        QueryWrapper::setDbConfig('mysql:host=127.0.0.1;dbname=app', 'user', '123456789');

        $result = QueryRelationManager::select('address', 'a')
            ->withSingle('city', 'city', 'c', 'a', ['id' =>  'city_id'])
            ->withMultiple('places', 'place', 'p', 'a', ['address_id' =>  'id'])
            ->withMultiple('comments', 'comment', 'cm', 'p', ['place_id' =>  'id'])
            ->all();

        $this->assertEquals(4, count($result));

//        $resultMap = ArrayHelper::index($result, 'id');
//
//        expect_that($resultMap[1]['city']['name'] == 'Moscow');
//        expect_that($resultMap[2]['city']['name'] == 'Moscow');
//        expect_that($resultMap[3]['city']['name'] == 'St. Petersburg');
//        expect_that($resultMap[4]['city']['name'] == 'St. Petersburg');
//
//        expect_that(count($resultMap[1]['places']) == 2);
//        expect_that(count($resultMap[2]['places']) == 1);
//        expect_that(count($resultMap[3]['places']) == 2);
//        expect_that(count($resultMap[4]['places']) == 1);
//
//        $mapPlaceIdToCommentsCount = [
//            1 => 3,
//            2 => 0,
//            3 => 1,
//            4 => 0,
//            5 => 1,
//            6 => 1,
//        ];
//
//        foreach($resultMap as $addressId => &$address) {
//            foreach($address['places'] as $place) {
//                expect_that(count($place['comments']) == $mapPlaceIdToCommentsCount[$place['id']]);
//            }
//        }
//        unset($address);
    }

//    /**
//     * @throws QueryRelationManagerException
//     */
//    public function testPlace()
//    {
//        $result = QueryRelationManager::select(Place::class, 'p')
//            ->withSingle('address', Address::class, 'a', 'p', ['id' => 'address_id'])
//            ->withSingle('city', City::class, 'c', 'a', ['id' => 'city_id'])
//            ->withMultiple('comments', Comment::class, 'cm', 'p', ['place_id' => 'id'],
//                'inner', 'and cm.mark >= :mark', [':mark' => 3])
//            ->modify('p', function(array &$place) {
//                $place['comments_count'] = count($place['comments']);
//                $place['mark_five_count'] = 0;
//                $place['mark_average'] = 0;
//
//                foreach($place['comments'] as $comment) {
//                    $place['mark_average'] += $comment['mark'];
//                    if($comment['mark'] == 5) {
//                        $place['mark_five_count']++;
//                    }
//                }
//
//                $place['mark_average'] /= $place['comments_count'];
//            })
//            ->all();
//
//        expect_that(count($result) == 4);
//
//        $resultMap = ArrayHelper::index($result, 'id');
//
//        expect_that($resultMap[1]['address']['name'] == 'Tverskaya st., 7');
//        expect_that($resultMap[3]['address']['name'] == 'Schipok st., 1');
//        expect_that($resultMap[5]['address']['name'] == 'Mayakovskogo st., 12');
//        expect_that($resultMap[6]['address']['name'] == 'Galernaya st., 3');
//
//        expect_that($resultMap[1]['address']['city']['name'] == 'Moscow');
//        expect_that($resultMap[3]['address']['city']['name'] == 'Moscow');
//        expect_that($resultMap[5]['address']['city']['name'] == 'St. Petersburg');
//        expect_that($resultMap[6]['address']['city']['name'] == 'St. Petersburg');
//
//        expect_that(count($resultMap[1]['comments']) == 2);
//        expect_that(count($resultMap[3]['comments']) == 1);
//        expect_that(count($resultMap[5]['comments']) == 1);
//        expect_that(count($resultMap[6]['comments']) == 1);
//
//        expect_that($resultMap[1]['comments_count'] == 2);
//        expect_that($resultMap[3]['comments_count'] == 1);
//        expect_that($resultMap[5]['comments_count'] == 1);
//        expect_that($resultMap[6]['comments_count'] == 1);
//
//        expect_that($resultMap[1]['mark_five_count'] == 1);
//        expect_that($resultMap[3]['mark_five_count'] == 1);
//        expect_that($resultMap[5]['mark_five_count'] == 0);
//        expect_that($resultMap[6]['mark_five_count'] == 0);
//
//        expect_that($resultMap[1]['mark_average'] == 4);
//        expect_that($resultMap[3]['mark_average'] == 5);
//        expect_that($resultMap[5]['mark_average'] == 4);
//        expect_that($resultMap[6]['mark_average'] == 3);
//    }
//
//    /**
//     * @throws QueryRelationManagerException
//     */
//    public function testCity()
//    {
//        $cityIds = City::find()->limit(2)->offset(1)->select('id')->column();
//        expect_that(count($cityIds) == 2);
//
//        $result = QueryRelationManager::select(City::class, 'c')
//            ->withMultiple('addresses', Address::class, 'a', 'c', ['city_id' => 'id'])
//            ->filter(function(Query $q) use ($cityIds) {
//                $q->andWhere(['c.id' => $cityIds])->orderBy(['a.id' => SORT_ASC]);
//            })
//            ->all();
//
//        expect_that(count($result) == 2);
//        expect_that(array_diff(ArrayHelper::getColumn($result, 'id'), $cityIds) == []);
//
//        $resultMap = ArrayHelper::index($result, 'id');
//
//        expect_that($resultMap[3]['name'] == 'Samara');
//        expect_that($resultMap[2]['name'] == 'St. Petersburg');
//
//        expect_that(count($resultMap[3]['addresses']) == 0);
//        expect_that(count($resultMap[2]['addresses']) == 2);
//    }

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
}
