<?php
namespace Vanio\DomainBundle\Translatable;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\Common\Util\ClassUtils;

trait TranslatableTrait
{
    /** @var string|null */
    private $currentLocale;

    /** @var string */
    private $defaultLocale = 'en';

    /** @var Collection|Translation[]|null */
    private $translations;

    /**
     * @return Collection|Translation[]
     */
    public function translations(): Collection
    {
        if (!$this->translations) {
            $this->translations = new ArrayCollection;
        }

        return $this->translations;
    }

    /**
     * @param string|null $locale
     * @return Translation|null
     */
    public function getTranslation(string $locale = null)
    {
        foreach ([$locale, $this->currentLocale, $this->defaultLocale] as $locale) {
            if ($locale === null) {
                continue;
            }

            $translation = $this->translations()[$locale];

            if ($translation && !$translation->isEmpty()) {
                return $translation;
            }
        }

        return null;
    }

    /**
     * @param Translation $translation
     * @return $this
     * @throws \InvalidArgumentException
     */
    public function addTranslation(Translation $translation): self
    {
        $translationClass = static::translationClass();

        if (!$translation instanceof $translationClass) {
            throw new \InvalidArgumentException(sprintf(
                'Invalid translation class "%s". Class "%s" must contain only translations of class "%s".',
                ClassUtils::getClass($translation),
                ClassUtils::getClass($this),
                $translationClass
            ));
        } elseif ($translation->locale() === null) {
            throw new \InvalidArgumentException(sprintf(
                'Cannot append translation of class "%s" with empty locale.',
                ClassUtils::getClass($translation)
            ));
        }

        $existingTranslation = $this->translations()[$translation->locale()];

        if ($existingTranslation && $existingTranslation !== $translation) {
            throw new \InvalidArgumentException(sprintf(
                'Cannot replace existing "%s" translation of class "%s".',
                $translation->locale(),
                ClassUtils::getClass($translation)
            ));
        }

        /** @noinspection PhpInternalEntityUsedInspection */
        $this->translations()[$translation->locale()] = $translation->setTranslatable($this);

        return $this;
    }

    public function removeTranslation(Translation $translation): self
    {
        unset($this->translations()[$translation->locale()]);

        return $this;
    }

    /**
     * @param \Traversable|Translation[] $translations
     */
    public function mergeTranslations($translations)
    {
        foreach ($translations as $translation) {
            $this->addTranslation($translation);
        }
    }

    public function translate(string $locale): Translation
    {
        if ($translation = $this->translations()[$locale]) {
            return $translation;
        }

        $translation = static::createTranslation()->setLocale($locale);
        $this->addTranslation($translation);

        return $translation;
    }

    /**
     * @return string|null
     */
    public function currentLocale()
    {
        return $this->currentLocale;
    }

    public function setCurrentLocale(string $currentLocale): self
    {
        $this->currentLocale = $currentLocale;

        return $this;
    }

    public function defaultLocale(): string
    {
        return $this->defaultLocale;
    }

    public function setDefaultLocale(string $defaultLocale): self
    {
        $this->defaultLocale = $defaultLocale;

        return $this;
    }

    /**
     * @param string $method
     * @param array $arguments
     * @return mixed
     */
    public function __call(string $method, array $arguments)
    {
        return $this->proxyCurrentLocaleTranslation($method, $arguments);
    }

    public static function translationClass(): string
    {
        return __CLASS__ . 'Translation';
    }

    protected function proxyCurrentLocaleTranslation(string $method, array $arguments = [])
    {
        return $this->getTranslation()->$method(...$arguments);
    }

    /**
     * @param string $locale
     * @return string|null
     */
    protected function resolveFallbackLocale(string $locale)
    {
        $position = strrpos($locale, '_');

        return $position !== false ? substr($locale, 0, $position) : null;
    }

    protected function createTranslation(): Translation
    {
        $translationClass = self::translationClass();

        return new $translationClass;
    }
}
