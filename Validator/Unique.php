<?php
namespace Vanio\DomainBundle\Validator;

use Symfony\Component\Validator\Constraint;

/**
 * @Annotation
 */
class Unique extends Constraint
{
    const NOT_UNIQUE_ERROR = '';

    protected static $errorNames = array(
        self::NOT_UNIQUE_ERROR => 'NOT_UNIQUE_ERROR',
    );

    public $fields;

    public $class;

    public $id = false;

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
