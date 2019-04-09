<?php

declare(strict_types=1);

namespace AbterPhp\Files\Service\RepoGrid;

use AbterPhp\Files\Grid\Factory\FileCategory as GridFactory;
use AbterPhp\Files\Orm\FileCategoryRepo as Repo;
use AbterPhp\Framework\Databases\Queries\FoundRows;
use AbterPhp\Framework\Http\Service\RepoGrid\RepoGridAbstract;
use Casbin\Enforcer;

class FileCategory extends RepoGridAbstract
{
    /**
     * File constructor.
     *
     * @param Enforcer    $enforcer
     * @param Repo        $repo
     * @param FoundRows   $foundRows
     * @param GridFactory $gridFactory
     */
    public function __construct(Enforcer $enforcer, Repo $repo, FoundRows $foundRows, GridFactory $gridFactory)
    {
        parent::__construct($enforcer, $repo, $foundRows, $gridFactory);
    }
}
