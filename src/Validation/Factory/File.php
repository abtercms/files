<?php

declare(strict_types=1);

namespace AbterPhp\Files\Validation\Factory;

use Opulence\Validation\Factories\ValidatorFactory;
use Opulence\Validation\IValidator;

class File extends ValidatorFactory
{
    /**
     * @return IValidator
     */
    public function createValidator(): IValidator
    {
        $validator = parent::createValidator();

        $validator
            ->field('id')
            ->uuid();

        $validator
            ->field('description')
            ->required();

        $validator
            ->field('category_id')
            ->uuid()
            ->required();

        return $validator;
    }
}
