<?php


namespace Smoren\Yii2\QueryRelationManager\Pdo;


use Smoren\Yii2\QueryRelationManager\Base\QueryRelationManagerBase;
use Smoren\Yii2\QueryRelationManager\Base\QueryWrapperInterface;
use Smoren\Yii2\QueryRelationManager\Base\QueryRelationManagerException;

/**
 * @inheritDoc
 * @package Smoren\Yii2\QueryRelationManager
 * @author Smoren <ofigate@gmail.com>
 */
class QueryRelationManager extends QueryRelationManagerBase
{

    /**
     * @inheritDoc
     */
    protected function getTableName(string $className): string
    {
        return $className;
    }

    /**
     * @inheritDoc
     */
    protected function createQuery(): QueryWrapperInterface
    {
        return new QueryWrapper();
    }

    /**
     * @inheritDoc
     * @throws QueryRelationManagerException
     */
    protected function getTableFields(string $className): array
    {
        $qw = new QueryWrapper();
        $qw->setRawSql('SHOW COLUMNS FROM '.addslashes($className));
        $rows = $qw->all();

        $result = [];
        foreach($rows as $row) {
            $result[] = $row['Field'];
        }

        return $result;
    }

    /**
     * @inheritDoc
     * @throws QueryRelationManagerException
     */
    protected function getPrimaryKey(string $className): array
    {
        $qw = new QueryWrapper();
        $qw->setRawSql("SHOW COLUMNS FROM ".addslashes($className)." WHERE Key = 'PRI'");
        $rows = $qw->all();

        $result = [];
        foreach($rows as $row) {
            $result[] = $row['Field'];
        }

        return $result;
    }
}