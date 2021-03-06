<?php
namespace Vanio\DomainBundle\Form;

use Symfony\Component\Form\FormEvents;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\Exception\UnexpectedTypeException;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Form\FormInterface;

class ResizeFormListener implements EventSubscriberInterface
{
    /** @var string */
    private $type;

    /** @var array */
    private $options;

    /** @var bool */
    private $allowAdd;

    /** @var bool */
    private $allowDelete;

    /** @var bool|callable */
    private $deleteEmpty;

    /**
     * @param string        $type
     * @param array         $options
     * @param bool          $allowAdd    Whether children could be added to the group
     * @param bool          $allowDelete Whether children could be removed from the group
     * @param bool|callable $deleteEmpty
     */
    public function __construct($type, array $options = [], $allowAdd = false, $allowDelete = false, $deleteEmpty = false)
    {
        $this->type = $type;
        $this->allowAdd = $allowAdd;
        $this->allowDelete = $allowDelete;
        $this->options = $options;
        $this->deleteEmpty = $deleteEmpty;
    }

    public static function getSubscribedEvents(): array
    {
        return [
            FormEvents::PRE_SET_DATA => 'preSetData',
            FormEvents::PRE_SUBMIT => 'preSubmit',
            // (MergeCollectionListener, MergeDoctrineCollectionListener)
            FormEvents::SUBMIT => ['onSubmit', 50],
        ];
    }

    public function preSetData(FormEvent $event): void
    {
        $form = $event->getForm();
        $data = $event->getData();

        if ($data === null) {
            $data = [];
        }

        if (!is_array($data) && !($data instanceof \Traversable && $data instanceof \ArrayAccess)) {
            throw new UnexpectedTypeException($data, 'array or (\Traversable and \ArrayAccess)');
        }

        foreach ($form as $name => $child) {
            $form->remove($name);
        }

        foreach ($data as $name => $value) {
            $encodedName = $this->encodeFormName($name);
            $form->add($encodedName, $this->type, array_replace([
                'property_path' => '['.$name.']',
            ], $this->options));
        }
    }

    public function preSubmit(FormEvent $event): void
    {
        $form = $event->getForm();
        $data = $event->getData();

        if ($data instanceof \Traversable && $data instanceof \ArrayAccess) {
            @trigger_error('Support for objects implementing both \Traversable and \ArrayAccess is deprecated since Symfony 3.1 and will be removed in 4.0. Use an array instead.', E_USER_DEPRECATED);
        }

        if (!is_array($data) && !($data instanceof \Traversable && $data instanceof \ArrayAccess)) {
            $data = [];
        }

        if ($this->allowDelete) {
            foreach ($form as $name => $child) {
                if (!isset($data[$name])) {
                    $form->remove($name);
                }
            }
        }

        if ($this->allowAdd) {
            foreach ($data as $name => $value) {
                if (!$form->has($name)) {
                    $decodedName = $this->decodeFormName($name);
                    $form->add($name, $this->type, array_replace([
                        'property_path' => '['.$decodedName.']',
                    ], $this->options));
                }
            }
        }
    }

    public function onSubmit(FormEvent $event): void
    {
        $form = $event->getForm();
        $data = $event->getData();

        // At this point, $data is an array or an array-like object that already contains the
        // new entries, which were added by the data mapper. The data mapper ignores existing
        // entries, so we need to manually unset removed entries in the collection.

        if ($data === null) {
            $data = [];
        }

        if (!is_array($data) && !($data instanceof \Traversable && $data instanceof \ArrayAccess)) {
            throw new UnexpectedTypeException($data, 'array or (\Traversable and \ArrayAccess)');
        }

        if ($this->deleteEmpty) {
            $previousData = $form->getData();
            /** @var FormInterface $child */
            foreach ($form as $name => $child) {
                $decodedName = $this->decodeFormName($name);
                $isNew = !isset($previousData[$decodedName]);
                $isEmpty = is_callable($this->deleteEmpty) ? call_user_func($this->deleteEmpty, $child->getData()) : $child->isEmpty();

                // $isNew can only be true if allowAdd is true, so we don't
                // need to check allowAdd again
                if ($isEmpty && ($isNew || $this->allowDelete)) {
                    unset($data[$decodedName]);
                    $form->remove($name);
                }
            }
        }

        // The data mapper only adds, but does not remove items, so do this
        // here
        if ($this->allowDelete) {
            $toDelete = [];

            foreach ($data as $name => $child) {
                if (!$form->has($name)) {
                    $toDelete[] = $name;
                }
            }

            foreach ($toDelete as $name) {
                unset($data[$this->decodeFormName($name)]);
            }
        }

        $event->setData($data);
    }

    private function encodeFormName(string $name): string
    {
        return '_' . str_replace('%', ':', rawurlencode($name));
    }

    private function decodeFormName(string $name): string
    {
        return substr(rawurldecode(str_replace(':', '%', $name)), 1);
    }
}
