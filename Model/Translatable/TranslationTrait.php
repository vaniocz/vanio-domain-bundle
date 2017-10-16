<?php
namespace Vanio\DomainBundle\Model\Translatable;

use Doctrine\Common\Util\ClassUtils;

trait TranslationTrait
{
    /** @var mixed */
    private $id;

    /** @var Translatable */
    private $translatable;

    /** @var string */
    private $locale;

    /**
     * @return string|null
     */
    public function translatable()
    {
        return $this->translatable;
    }

    /**
     * @throws \InvalidArgumentException
     * @internal
     */
    public function setTranslatable(Translatable $translatable): self
    {
        if ($this->translatable !== null && $this->translatable !== $translatable) {
            throw new \InvalidArgumentException(sprintf(
                'Trying to assign translation of class "%s" to a different translatable of class "%s". Reassigning translations is forbidden.',
                ClassUtils::getClass($this),
                ClassUtils::getClass($this->translatable)
            ));
        }

        $this->translatable = $translatable;

        return $this;
    }

    /**
     * @return string|null
     */
    public function locale()
    {
        return $this->locale;
    }

    /**
     * @throws \InvalidArgumentException
     */
    public function setLocale(string $locale): self
    {
        if ($this->translatable && $this->locale !== null && $this->locale !== $locale) {
            throw new \InvalidArgumentException(sprintf(
                'Trying to overwrite locale "%s" with locale "%s". Overwriting locales of already assigned translations is forbidden.',
                $this->locale,
                $locale
            ));
        }

        $this->locale = $locale;

        return $this;
    }

    public function isEmpty(): bool
    {
        foreach (get_object_vars($this) as $property => $value) {
            if ($value !== null && $value !== '' && !in_array($property, ['id', 'translatable', 'locale'])) {
                return false;
            }
        }

        return true;
    }
}
