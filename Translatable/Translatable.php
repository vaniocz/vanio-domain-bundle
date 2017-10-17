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
     * @return object
     */
    public function translate(string $locale);

    public static function translationClass(): string;
}
