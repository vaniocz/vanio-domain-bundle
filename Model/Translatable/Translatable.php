<?php
namespace Vanio\DomainBundle\Model\Translatable;

interface Translatable
{
    public function translations(): TranslatableCollection;

    /**
     * @param string $locale
     * @return object
     */
    public function translate(string $locale);

    public static function translationClass(): string;
}
