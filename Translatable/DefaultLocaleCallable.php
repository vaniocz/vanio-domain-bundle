<?php
namespace Vanio\DomainBundle\Translatable;

class DefaultLocaleCallable
{
    /** @var string */
    private $locale;

    public function __construct(string $locale = 'en')
    {
        $this->locale = $locale;
    }

    public function __invoke(): string
    {
        return $this->locale;
    }
}
