<?php
namespace Vanio\DomainBundle\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\DataMapperInterface;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Vanio\DomainBundle\Model\Location;

class LocationType extends AbstractType implements DataMapperInterface
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('address', TextType::class)
            ->add('latitude', NumberType::class, ['scale' => 8])
            ->add('longitude', NumberType::class, ['scale' => 8]);
        $builder->setDataMapper($this);
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => Location::class,
            'empty_data' => null,
            'error_bubbling' => false,
        ]);
    }

    /**
     * @param Location|null $data
     * @param \Iterator|FormInterface[] $forms
     */
    public function mapDataToForms($data, $forms)
    {
        $forms = iterator_to_array($forms);
        /** @var FormInterface[] $forms */
        $forms['address']->setData($data instanceof Location ? $data->address() : null);
        $forms['latitude']->setData($data instanceof Location ? $data->latitude() : null);
        $forms['longitude']->setData($data instanceof Location ? $data->longitude() : null);
    }

    /**
     * @param \Iterator|FormInterface[] $forms
     * @param mixed $data
     */
    public function mapFormsToData($forms, &$data)
    {
        $forms = iterator_to_array($forms);
        /** @var FormInterface[] $forms */
        $address = $forms['address']->getData();
        $latitude = $forms['latitude']->getData();
        $longitude = $forms['longitude']->getData();
        $data = $address === null || $latitude === null || $longitude === null
            ? null
            : new Location($address, $latitude, $longitude);
    }
}
