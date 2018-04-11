<?php
namespace Vanio\DomainBundle\Validator;

use Symfony\Component\Validator\Constraint;

/**
 * @Annotation
 */
class Unique extends Constraint
{
    const NOT_UNIQUE_ERROR = 'ee3151d8-cf49-4120-87cb-32847382cbfc';

    /** @var string[] */
    protected static $errorNames = [
        self::NOT_UNIQUE_ERROR => 'NOT_UNIQUE_ERROR',
    ];

    /** @var string */
    public $class;

    /** @var string[] */
    public $properties;

    /** @var string|null */
    public $id = null;

    /** @var string */
    public $message = 'This value is already used.';

    /** @var string|null */
    public $errorPath;

    /** @var bool */
    public $ignoreNull = true;

    /**
     * @return string[]
     */
    public function getRequiredOptions(): array
    {
        return ['class', 'properties'];
    }

    public function getTargets(): string
    {
        return self::CLASS_CONSTRAINT;
    }

    public function validatedBy(): string
    {
        return 'vanio_domain.validator.unique';
    }
}
