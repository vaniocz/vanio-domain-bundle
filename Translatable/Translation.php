<?php
namespace Vanio\DomainBundle\Translatable;

interface Translation
{
    /**
     * @return Translatable|null
     */
    public function translatable();

    /**
     * @param Translatable $translatable
     * @return $this
     * @internal
     */
    public function setTranslatable(Translatable $translatable);

    /**
     * @return string|null
     */
    public function locale();

    /**
     * @param string $locale
     * @return $this
     */
    public function setLocale(string $locale);

    public function isEmpty(): bool;

    public static function translatableClass(): string;
}
