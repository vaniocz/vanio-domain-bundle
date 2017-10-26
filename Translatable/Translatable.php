<?php
namespace Vanio\DomainBundle\Translatable;

use Doctrine\Common\Collections\Collection;

interface Translatable
{
    /**
     * @return Collection|Translation[]
     */
    public function translations(): Collection;

    /**
     * @param string|null $locale
     * @param bool|null $fallbackToDefaultLocale
     * @return Translation
     */
    public function getTranslation(string $locale = null, bool $fallbackToDefaultLocale = null);

    /**
     * @param Translation $translation
     * @return $this
     */
    public function addTranslation(Translation $translation);

    /**
     * @param Translation $translation
     * @return $this
     */
    public function removeTranslation(Translation $translation);

    /**
     * @param string $locale
     * @return Translation
     */
    public function translate(string $locale);

    public static function translationClass(): string;
}
