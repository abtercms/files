<?php

declare(strict_types=1);

namespace AbterPhp\Files\Http\Controllers\Api;

use AbterPhp\Admin\Http\Controllers\ApiAbstract;
use AbterPhp\Files\Service\Execute\FileDownload as RepoService;
use AbterPhp\Framework\Config\EnvReader;
use AbterPhp\Framework\Databases\Queries\FoundRows;
use Opulence\Http\Responses\Response;
use Opulence\Http\Responses\ResponseHeaders;
use Psr\Log\LoggerInterface;

class FileDownload extends ApiAbstract
{
    const ENTITY_SINGULAR = 'fileDownload';
    const ENTITY_PLURAL   = 'fileDownloads';

    /** @var RepoService */
    protected $repoService;

    /**
     * FileDownload constructor.
     *
     * @param LoggerInterface $logger
     * @param RepoService     $repoService
     * @param FoundRows       $foundRows
     * @param EnvReader       $envReader
     */
    public function __construct(
        LoggerInterface $logger,
        RepoService $repoService,
        FoundRows $foundRows,
        EnvReader $envReader
    ) {
        parent::__construct($logger, $repoService, $foundRows, $envReader);
    }

    /**
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     *
     * @param string $entityId
     *
     * @return Response
     */
    public function update(string $entityId): Response
    {
        $response = new Response();
        $response->setStatusCode(ResponseHeaders::HTTP_NOT_IMPLEMENTED);

        return $response;
    }
}
