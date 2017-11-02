<?php
namespace Vanio\DomainBundle\Validator;

use Symfony\Component\Validator\Constraint;

/**
 * @Annotation
 */
class UniqueCollection extends Constraint
{
    const NOT_UNIQUE_COLLECTION_ERROR = 'cca50f29-49a1-43bb-b2af-f4b3dd7cac8f';

    /** @var string[] */
    protected static $errorNames = [
        self::NOT_UNIQUE_COLLECTION_ERROR => 'NOT_UNIQUE_COLLECTION_ERROR',
    ];

    /** @var string|null */
    public $propertyPath;

    /** @var string|null */
    public $errorPath;

    /** @var bool */
    public $ignoreNull = true;

    /** @var string */
    public $message = 'This value must be unique.';

    public function getDefaultOption(): string
    {
        return 'propertyPath';
    }

    public function getTargets(): string
    {
        return self::CLASS_CONSTRAINT;
    }
}
