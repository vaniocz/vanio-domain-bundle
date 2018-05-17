<?php
namespace Vanio\DomainBundle\Form;

use Ramsey\Uuid\Exception\InvalidUuidStringException;
use Ramsey\Uuid\Uuid;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\DataTransformerInterface;
use Symfony\Component\Form\Exception\TransformationFailedException;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class UuidType extends AbstractType implements DataTransformerInterface
{
    /**
     * @param FormBuilderInterface $builder
     * @param mixed[] $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->addModelTransformer($this);
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'invalid_message' => 'Invalid UUID string.',
            'documentation' => [
                'type' => 'uuid',
                'example' => '7e57d004-2b97-0e7a-b45f-5387367791cd',
            ]]
        );
    }

    /**
     * @param Uuid|null $value
     * @return string
     */
    public function transform($value): string
    {
        return (string) $value;
    }

    /**
     * @param string|null $value
     * @return Uuid|null
     */
    public function reverseTransform($value)
    {
        try {
            return $value === null || $value === '' ? null : Uuid::fromString($value);
        } catch (InvalidUuidStringException $e) {
            throw new TransformationFailedException($e->getMessage(), 0, $e);
        }
    }

    public function getParent(): string
    {
        return TextType::class;
    }
}
