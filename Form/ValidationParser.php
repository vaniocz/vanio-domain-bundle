<?php
namespace Vanio\DomainBundle\Form;

interface ValidationParser
{
    function parseValidationRules(string $class): array;
}
