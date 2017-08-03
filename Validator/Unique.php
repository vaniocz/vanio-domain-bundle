<?php
namespace Vanio\DomainBundle\Validator;

use Symfony\Component\Validator\Constraint;

/**
 * @Annotation
 */
class Unique extends Constraint
{
    const NOT_UNIQUE_ERROR = 'ee3151d8-cf49-4120-87cb-32847382cbfc';

    /** @var array */
    protected static $errorNames = [
        self::NOT_UNIQUE_ERROR => 'NOT_UNIQUE_ERROR',
    ];

    /** @var array*/
    public $fields;

    /** @var string */
    public $class;

    /** @var string|null */
    public $id = null;

    /** @var string */
    public $message = 'This value is already used.';

    public function getRequiredOptions()
    {
        return [ 'fields', 'class' ];
    }

    public function getTargets()
    {
        return self::CLASS_CONSTRAINT;
    }

    public function validatedBy()
    {
        return 'vanio_domain.validator.unique';
    }
}
