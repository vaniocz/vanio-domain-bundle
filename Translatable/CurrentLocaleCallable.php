<?php
namespace Vanio\DomainBundle\Translatable;

use Symfony\Component\HttpFoundation\RequestStack;

class CurrentLocaleCallable
{
    /** @var RequestStack */
    private $requestStack;

    public function __construct(RequestStack $requestStack)
    {
        $this->requestStack = $requestStack;
    }

    /**
     * @return string|null
     */
    public function __invoke()
    {
        $request = $this->requestStack->getCurrentRequest();

        return $request ? $request->getLocale() : null;
    }
}
