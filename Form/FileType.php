<?php
namespace Vanio\DomainBundle\Form;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\DataMapperInterface;
use Symfony\Component\Form\Extension\Core\Type\FileType as BaseFileType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormConfigBuilder;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\File\File as SymfonyFile;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\NotBlank;
use Vanio\DomainBundle\Model\File;
use Vanio\Stdlib\Objects;

class FileType extends AbstractType implements DataMapperInterface, EventSubscriberInterface
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->add('file', BaseFileType::class, $options['options'] + [
            'data_class' => $options['multiple'] ? null : SymfonyFile::class,
            'empty_data' => $options['multiple'] ? [] : null,
            'multiple' => $options['multiple'],
            'label' => false,
            'error_bubbling' => false,
        ]);
        $builder->setDataMapper($this);
        $builder->addEventSubscriber($this);
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver
            ->setDefaults([
                'error_bubbling' => false,
                'class' => File::class,
                'multiple' => false,
                'options' => [],
                'required_message' => 'Choose a file.',
            ])
            ->setAllowedTypes('class', 'string')
            ->setAllowedTypes('multiple', 'bool')
            ->setAllowedTypes('options', 'array');
    }

    /**
     * @param File|File[]|null $data
     * @param \Iterator|FormInterface[] $forms
     */
    public function mapDataToForms($data, $forms)
    {
        $forms = iterator_to_array($forms);
        /** @var FormInterface $form */
        $form = reset($forms);
        $parent = $form->getParent();
        $class = $parent->getConfig()->getOption('class');

        if ($parent->getConfig()->getOption('multiple')) {
            $formData = [];

            foreach ((array) $data as $file) {
                if (is_a($file, $class, true)) {
                    $formData[] = $file->file();
                }
            }
        } elseif (is_a($data, $class, true)) {
            $formData = $data->file();
        }

        $form->setData($formData ?? null);
    }

    /**
     * @param \RecursiveIteratorIterator|FormInterface[] $forms
     * @param mixed $data
     */
    public function mapFormsToData($forms, &$data)
    {
        $forms = iterator_to_array($forms);
        /** @var FormInterface $form */
        $form = reset($forms);
        $class = $form->getParent()->getConfig()->getOption('class');
        $formData = $form->getData();

        if ($form->getParent()->getConfig()->getOption('multiple')) {
            $data = array_intersect_key($data, array_filter($formData));

            foreach ((array) $formData as $file) {
                if ($file instanceof UploadedFile) {
                    $data[] = new $class($file);
                }
            }
        } else {
            $data = $formData
                ? new $class($formData)
                : $data ?: null;
        }
    }

    public function getBlockPrefix(): string
    {
        return 'vanio_file';
    }

    public static function getSubscribedEvents(): array
    {
        return [
            FormEvents::PRE_SET_DATA => 'onPreSetData',
            FormEvents::PRE_SUBMIT => 'onPreSubmit',
        ];
    }

    /**
     * @internal
     */
    public function onPreSetData(FormEvent $formEvent)
    {
        $form = $formEvent->getForm();

        if (!$form->isRequired() || !$form->getConfig() instanceof FormConfigBuilder) {
            return;
        }

        $fileForm = $form->get('file');
        $options = $fileForm->getConfig()->getOptions();
        $options['constraints'][] = new NotBlank([
            'message' => $form->getConfig()->getOption('required_message'),
            'groups' => $this->resolveValidationGroups($fileForm),
        ]);
        Objects::setPropertyValue($fileForm->getConfig(), 'options', $options, FormConfigBuilder::class);
    }

    /**
     * @internal
     */
    public function onPreSubmit(FormEvent $formEvent)
    {
        $data = $formEvent->getData();

        if (($data['file'] ?? null) === [null]) {
            $data['file'] = null;
            $formEvent->setData($data);
        }
    }

    /**
     * @param FormInterface $form
     * @return string|string[]|null
     */
    private function resolveValidationGroups(FormInterface $form)
    {
        do {
            $validationGroups = $form->getConfig()->getOption('validation_groups');
            $form = $form->getParent();
        } while (!$validationGroups && $form);

        return $validationGroups;
    }
}
