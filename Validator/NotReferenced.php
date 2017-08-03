<?php
namespace Vanio\DomainBundle\Validator;

use Symfony\Component\Validator\Constraint;

/**
 * @Annotation
 */
class NotReferenced extends Constraint
{
    const IS_REFERENCED_ERROR = '0d52565f-bc0f-41ee-9261-4dc68d29548c';

    /** @var array */
    protected static $errorNames = [
        self::IS_REFERENCED_ERROR => 'IS_REFERENCED_ERROR',
    ];

    /** @var string */
    public $field;

    /** @var string */
    public $relatedEntity;

    /** @var string|null */
    public $relatedField = null;

    /** @var string */
    public $message = 'The entity is referenced by another entity.';

    public function getRequiredOptions(): array
    {
        return [ 'field', 'relatedEntity' ];
    }

    public function getTargets(): string
    {
        return self::CLASS_CONSTRAINT;
    }

    public function validatedBy(): string
    {
        return 'vanio_domain.validator.not_referenced';
    }
}
