<?php
namespace Vanio\DomainBundle\Model\Translatable;

interface Translation
{
    public function translatable(): Translatable;

    /**
     * @internal
     */
    public function setTranslatable(Translatable $translatable): self;

    public function locale(): string;

    public function setLocale(string $locale): self;

    public function isEmpty(): bool;
}
