<?php
namespace Vanio\DomainBundle\Form;

use Fp\JsFormValidatorBundle\Factory\JsFormValidatorFactory as BaseJsFormValidatorFactory;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Form\Form;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\Constraints\NotBlank;
use Vanio\Stdlib\Strings;

class JsFormValidatorFactory extends BaseJsFormValidatorFactory
{
    /** @var ValidationConstraintsGuesser|null */
    private $validationConstraintsGuesser;

    public function setValidationsConstraintsGuesser(ValidationConstraintsGuesser $validationConstraintsGuesser)
    {
        $this->validationConstraintsGuesser = $validationConstraintsGuesser;
    }

    /**
     * @internal
     */
    public function isNotNotBlankConstraint(Constraint $constraint): bool
    {
        return !$constraint instanceof NotBlank;
    }

    protected function getValidationData(Form $form): array
    {
        $validationData = parent::getValidationData($form);

        if (!$parent = $form->getParent()) {
            return $validationData;
        } elseif (!$class = $parent->getConfig()->getOption('class', $parent->getConfig()->getDataClass())) {
            return $validationData;
        } elseif (!$constraints = $this->validationConstraintsGuesser->guessValidationConstraints($class)) {
            return $validationData;
        }

        $constraints = array_merge(...array_values($constraints));

        if (!$parent->isRequired()) {
            $constraints = array_filter($constraints, [$this, 'isNotNotBlankConstraint']);
        }

        if ($constraints) {
            $this->composeValidationData($validationData['form'], $constraints, []);
            $validationData['form']['groups'] = $this->getValidationGroups($form);
        }

        return $validationData;
    }

    protected function parseConstraints(array $constraints): array
    {
        $data = [];

        foreach ($constraints as $constraint) {
            foreach ($constraint as $property => &$value) {
                if (Strings::contains(strtolower($property), 'message')) {
                    $value = $this->replaceConstraintMessageParameters($constraint, $this->translateMessage($value));
                }
            }

            if (!$constraint instanceof UniqueEntity) {
                $data[get_class($constraint)][] = $constraint;
            }
        }

        return $data;
    }

    private function replaceConstraintMessageParameters(Constraint $constraint, string $message): string
    {
        if (!$mappings = $this->validationConstraintsGuesser->getConstraintMessageParameterMappings($constraint)) {
            return $message;
        }

        $messageParameters = [];

        foreach ($mappings as $from => $to) {
            $messageParameters["{{ $from }}"] = "{{ $to }}";
        }

        return strtr($message, $messageParameters);
    }
}
