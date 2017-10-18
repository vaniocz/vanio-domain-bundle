<?php
namespace Vanio\DomainBundle\Specification;

use Happyr\DoctrineSpecification\BaseSpecification;
use Happyr\DoctrineSpecification\Filter\Equals;
use Happyr\DoctrineSpecification\Logic\AndX;
use Happyr\DoctrineSpecification\Query\Join;

class Locale extends BaseSpecification
{
    /**
     * @var string
     */
    private $locale;

    public function __construct(string $locale, $dqlAlias = null)
    {
        parent::__construct($dqlAlias);
        $this->locale = $locale;
    }

    public function getSpec(): AndX
    {
        return new AndX(
            new Join('translations', 't'),
            new Equals('locale', $this->locale, 't')
        );
    }
}
