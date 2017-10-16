<?php
namespace Vanio\DomainBundle\Model\Translatable;

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

    /** @var TranslatableCollection|Translatable[]|null */
    private $translatableCollection;

    public function translations(): Collection
    {
        if (!$this->translations) {
            $this->translations = new ArrayCollection;
        }

        if (!$this->translatableCollection) {
            $this->translatableCollection = new TranslatableCollection($this->translations, $this);
        }

        return $this->translatableCollection;
    }

    public function translate(string $locale): Translation
    {
        if ($translation = $this->translations()[$locale]) {
            return $translation;
        }

        $translation = static::createTranslation();
        $this->translatableCollection[$locale] = $translation;

        return $translation;
    }

    /**
     * @param mixed $locale the current locale
     */
    public function setCurrentLocale($locale)
    {
        $this->currentLocale = $locale;
    }

    /**
     * @return Returns the current locale
     */
    public function getCurrentLocale()
    {
        return $this->currentLocale ?: $this->getDefaultLocale();
    }

    /**
     * @param mixed $locale the default locale
     */
    public function setDefaultLocale($locale)
    {
        $this->defaultLocale = $locale;
    }

    /**
     * @return Returns the default locale
     */
    public function getDefaultLocale()
    {
        return $this->defaultLocale;
    }

    /**
     * An extra feature allows you to proxy translated fields of a translatable entity.
     *
     * @param string $method
     * @param array $arguments
     *
     * @return mixed The translated value of the field for current locale
     */
    protected function proxyCurrentLocaleTranslation($method, array $arguments = [])
    {
        return call_user_func_array(
            [$this->translate($this->getCurrentLocale()), $method],
            $arguments
        );
    }

    protected function computeFallbackLocale($locale)
    {
        if (strrchr($locale, '_') !== false) {
            return substr($locale, 0, -strlen(strrchr($locale, '_')));
        }

        return false;
    }

    public static function translationClass(): string
    {
        return __CLASS__ . 'Translation';
    }

    protected function createTranslation(): Translation
    {
        $translationClass = self::translationClass();

        return new $translationClass;
    }
}
