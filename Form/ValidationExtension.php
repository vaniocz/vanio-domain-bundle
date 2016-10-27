<?php
namespace Vanio\DomainBundle\Form;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Form\AbstractTypeExtension;
use Symfony\Component\Form\Extension\Core\Type\FormType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormConfigBuilder;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\Translation\TranslatorInterface;
use Vanio\Stdlib\Objects;

class ValidationExtension extends AbstractTypeExtension implements EventSubscriberInterface
{
    /** @var TranslatorInterface */
    private $translator;

    public function __construct(TranslatorInterface $translator)
    {
        $this->translator = $translator;
    }

    public static function getSubscribedEvents(): array
    {
        return [FormEvents::PRE_SET_DATA => 'onPreSetData'];
    }

    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->addEventSubscriber($this);
    }

    public function onPreSetData(FormEvent $formEvent)
    {
        $formConfig = $formEvent->getForm()->getConfig();
        $dataMapper = $formConfig->getDataMapper();

        if ($formConfig instanceof FormConfigBuilder) {
            if ($dataMapper && !$dataMapper instanceof ValidatingDataMapper) {
                $validatingDataMapper = new ValidatingDataMapper($dataMapper, $this->translator);
                Objects::setPropertyValue($formConfig, 'dataMapper', $validatingDataMapper, FormConfigBuilder::class);
            }
        }
    }

    public function getExtendedType(): string
    {
        return FormType::class;
    }
}
