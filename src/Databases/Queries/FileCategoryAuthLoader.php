<?php

declare(strict_types=1);

namespace AbterPhp\Files\Databases\Queries;

use AbterPhp\Admin\Databases\Queries\IAuthLoader;
use AbterPhp\Admin\Exception\Database;
use Opulence\Databases\ConnectionPools\ConnectionPool;
use Opulence\QueryBuilders\MySql\QueryBuilder;

/** @phan-file-suppress PhanTypeMismatchArgument */

class FileCategoryAuthLoader implements IAuthLoader
{
    /** @var ConnectionPool */
    protected $connectionPool;

    /**
     * BlockCache constructor.
     *
     * @param ConnectionPool $connectionPool
     */
    public function __construct(ConnectionPool $connectionPool)
    {
        $this->connectionPool = $connectionPool;
    }

    /**
     * @return array
     */
    public function loadAll(): array
    {
        $query = (new QueryBuilder())
            ->select('ug.identifier AS v0', 'fc.identifier AS v1')
            ->from('user_groups_file_categories', 'ugfc')
            ->innerJoin('file_categories', 'fc', 'ugfc.file_category_id = fc.id AND fc.deleted = 0')
            ->innerJoin('user_groups', 'ug', 'ugfc.user_group_id = ug.id AND ug.deleted = 0')
        ;

        $connection = $this->connectionPool->getReadConnection();
        $statement  = $connection->prepare($query->getSql());
        $statement->bindValues($query->getParameters());
        if (!$statement->execute()) {
            throw new Database($statement->errorInfo());
        }

        return $statement->fetchAll(\PDO::FETCH_ASSOC);
    }
}
