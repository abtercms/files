<?php

declare(strict_types=1);

namespace AbterPhp\Files\Service\Execute\Api;

use AbterPhp\Admin\Service\Execute\RepoServiceAbstract;
use AbterPhp\Files\Domain\Entities\File as Entity;
use AbterPhp\Files\Domain\Entities\FileCategory;
use AbterPhp\Files\Orm\FileCategoryRepo;
use AbterPhp\Files\Orm\FileRepo as GridRepo;
use AbterPhp\Files\Validation\Factory\Api\File as ValidatorFactory;
use AbterPhp\Framework\Domain\Entities\IStringerEntity;
use AbterPhp\Framework\Filesystem\Uploader;
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
     * @param IStringerEntity $entity
     * @param string[]        $postData
     * @param UploadedFile[]  $fileData
     *
     * @return bool
     * @throws OrmException
     */
    public function update(IStringerEntity $entity, array $postData, array $fileData): bool
    {
        assert($entity instanceof Entity, new \InvalidArgumentException());

        $this->fillEntity($entity, $postData, $fileData);

        if (!empty($fileData)) {
            $this->deleteFile($entity);
            $this->uploadFile($entity, $fileData);
        }

        $this->commitUpdate($entity);

        return true;
    }

    /**
     * @param IStringerEntity $entity
     *
     * @return bool
     * @throws OrmException
     */
    public function delete(IStringerEntity $entity): bool
    {
        assert($entity instanceof Entity, new \InvalidArgumentException());

        $this->deleteFile($entity);

        $this->repo->delete($entity);

        $this->commitDelete($entity);

        return true;
    }

    /**
     * @param IStringerEntity $entity
     */
    public function deleteFile(IStringerEntity $entity)
    {
        assert($entity instanceof Entity, new \InvalidArgumentException());

        $this->uploader->delete($entity->getOldFilesystemName());
    }

    /**
     * @param IStringerEntity $entity
     * @param UploadedFile[]  $fileData
     */
    public function uploadFile(IStringerEntity $entity, array $fileData)
    {
        assert($entity instanceof Entity, new \InvalidArgumentException());

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

        return new Entity($entityId, '', '', '', '', $fileCategory, null);
    }

    /**
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     *
     * @param IStringerEntity $entity
     * @param array           $postData
     * @param UploadedFile[]  $fileData
     *
     * @return Entity
     * @throws OrmException
     */
    protected function fillEntity(IStringerEntity $entity, array $postData, array $fileData): IStringerEntity
    {
        assert($entity instanceof Entity, new \InvalidArgumentException());

        $categoryId     = $postData['category_id'];
        $description    = $postData['description'];
        $filesystemName = $postData['filesystem_name'];
        $publicName     = $postData['public_name'];
        $mime           = $postData['mime'];

        /** @var FileCategory $fileCategory */
        $fileCategory = $this->fileCategoryRepo->getById($categoryId);

        $entity
            ->setDescription($description)
            ->setCategory($fileCategory)
            ->setFilesystemName($filesystemName)
            ->setPublicName($publicName)
            ->setMime($mime);

        return $entity;
    }
}
