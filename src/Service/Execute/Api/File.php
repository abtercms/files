<?php

declare(strict_types=1);

namespace AbterPhp\Files\Service\Execute\Api;

use AbterPhp\Files\Domain\Entities\File as Entity;
use AbterPhp\Files\Domain\Entities\FileCategory;
use AbterPhp\Files\Orm\FileCategoryRepo;
use AbterPhp\Files\Orm\FileRepo as GridRepo;
use AbterPhp\Files\Validation\Factory\Api\File as ValidatorFactory;
use AbterPhp\Framework\Domain\Entities\IStringerEntity;
use AbterPhp\Framework\Filesystem\Uploader;
use AbterPhp\Framework\Http\Service\Execute\RepoServiceAbstract;
use Cocur\Slugify\Slugify;
use Opulence\Events\Dispatchers\IEventDispatcher;
use Opulence\Http\Requests\UploadedFile;
use Opulence\Orm\IUnitOfWork;
use Opulence\Orm\OrmException;

class File extends RepoServiceAbstract
{
    const INPUT_NAME_FILE = 'file';

    /** @var Slugify */
    protected $slugify;

    /** @var FileCategoryRepo */
    protected $fileCategoryRepo;

    /** @var Uploader */
    protected $uploader;

    /**
     * File constructor.
     *
     * @param GridRepo         $repo
     * @param ValidatorFactory $validatorFactory
     * @param IUnitOfWork      $unitOfWork
     * @param IEventDispatcher $eventDispatcher
     * @param Slugify          $slugify
     * @param FileCategoryRepo $fileCategoryRepo
     * @param Uploader         $uploader
     */
    public function __construct(
        GridRepo $repo,
        ValidatorFactory $validatorFactory,
        IUnitOfWork $unitOfWork,
        IEventDispatcher $eventDispatcher,
        Slugify $slugify,
        FileCategoryRepo $fileCategoryRepo,
        Uploader $uploader
    ) {
        parent::__construct($repo, $validatorFactory, $unitOfWork, $eventDispatcher);

        $this->slugify          = $slugify;
        $this->fileCategoryRepo = $fileCategoryRepo;
        $this->uploader         = $uploader;
    }

    /**
     * @param string[]       $postData
     * @param UploadedFile[] $fileData
     *
     * @return Entity
     * @throws OrmException
     */
    public function create(array $postData, array $fileData): IStringerEntity
    {
        $entity = $this->fillEntity($this->createEntity(''), $postData, $fileData);

        $this->uploadFile($entity, $fileData);

        if ($this->uploader->getErrors()) {
            return $entity;
        }

        $this->repo->add($entity);

        $this->commitCreate($entity);

        return $entity;
    }

    /**
     * @param Entity         $entity
     * @param string[]       $postData
     * @param UploadedFile[] $fileData
     *
     * @return bool
     * @throws OrmException
     */
    public function update(IStringerEntity $entity, array $postData, array $fileData): bool
    {
        $this->fillEntity($entity, $postData, $fileData);

        if (!empty($fileData)) {
            $this->deleteFile($entity);
            $this->uploadFile($entity, $fileData);
        }

        $this->commitUpdate($entity);

        return true;
    }

    /**
     * @param Entity $entity
     *
     * @return bool
     * @throws OrmException
     */
    public function delete(IStringerEntity $entity): bool
    {
        $this->deleteFile($entity);

        $this->repo->delete($entity);

        $this->commitDelete($entity);

        return true;
    }

    /**
     * @param Entity $entity
     */
    public function deleteFile(IStringerEntity $entity)
    {
        $this->uploader->delete($entity->getOldFilesystemName());
    }

    /**
     * @param Entity         $entity
     * @param UploadedFile[] $fileData
     */
    public function uploadFile(Entity $entity, array $fileData)
    {
        $paths = $this->uploader->persist($fileData);

        if (!$paths) {
            return;
        }

        $entity->setFilesystemName($paths[static::INPUT_NAME_FILE]);
        $entity->setPublicName($fileData[static::INPUT_NAME_FILE]->getTempFilename());
    }

    /**
     * @param string $entityId
     *
     * @return Entity
     */
    public function createEntity(string $entityId): IStringerEntity
    {
        $fileCategory = new FileCategory('', '', '', false, []);

        return new Entity($entityId, '', '', '', $fileCategory, null);
    }

    /**
     * @param Entity         $entity
     * @param array          $postData
     * @param UploadedFile[] $fileData
     *
     * @return Entity
     * @throws OrmException
     */
    protected function fillEntity(IStringerEntity $entity, array $postData, array $fileData): IStringerEntity
    {
        $description = (string)$postData['description'];

        /** @var FileCategory $fileCategory */
        $fileCategory = $this->fileCategoryRepo->getById($postData['category_id']);

        $entity
            ->setDescription($description)
            ->setCategory($fileCategory);

        if (array_key_exists('filesystem_name', $postData)) {
            $entity
                ->setFilesystemName($postData['filesystem_name'])
                ->setPublicName($postData['public_name']);
        }

        return $entity;
    }
}
