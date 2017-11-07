<?php
namespace Vanio\DomainBundle\Translatable;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;

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

    public function getTranslation(string $locale = null, bool $fallbackToDefaultLocale = null): Translation
    {
        if ($locale === null) {
            $locale = $this->currentLocale;
        }

        $locales = [$locale];

        if ($locale !== null) {
            $locales[] = $this->resolveFallbackLocale($locale);
        }

        if ($fallbackToDefaultLocale ?? $this->shouldFallbackToDefaultLocale()) {
            $locales[] = $this->defaultLocale;
        }

        foreach ($locales as $locale) {
            if ($locale === null) {
                continue;
            }

            $translation = $this->translations()[$locale];

            if ($translation && !$translation->isEmpty()) {
                return $translation;
            }
        }

        return $this->createTranslation();
    }

    /**
     * @param Translation $translation
     * @return $this
     * @throws TranslationException
     */
    public function addTranslation(Translation $translation): self
    {
        $translationClass = static::translationClass();

        if (!$translation instanceof $translationClass) {
            throw TranslationException::invalidClass($translation, $this);
        } elseif ($translation->locale() === null) {
            throw TranslationException::emptyLocale($translation);
        }

        $existingTranslation = $this->translations()[$translation->locale()];

        if ($existingTranslation && $existingTranslation !== $translation) {
            throw TranslationException::duplicate($translation);
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

    protected function shouldFallbackToDefaultLocale(): bool
    {
        return false;
    }

    /**
     * @param string $method
     * @param array $arguments
     * @return mixed
     */
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
