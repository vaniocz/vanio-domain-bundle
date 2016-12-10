<?php
namespace Vanio\DomainBundle\Tests\Specifaction;

use PHPUnit\Framework\TestCase;
use Vanio\DomainBundle\Specification\TextSearchSpecification;

class TextSearchSpecificationTest extends TestCase
{
    /** @var TextSearchSpecification */
    private $spec;

    protected function setUp()
    {
        $this->spec = new class extends TextSearchSpecification {
            public function searchTerm(string $term): string
            {
                return $this->processSearchTerm($term);
            }
        };
    }

    function test_search_term_is_trimmed()
    {
        $this->assertSame('abc', $this->spec->searchTerm(' abc  '));
    }

    function test_spaces_are_replaced_by_or_operators()
    {
        $this->assertSame('a|b|c', $this->spec->searchTerm(' a b     c '));
    }

    function test_follow_by_operator()
    {
        $this->assertSame('a<->b<2>c', $this->spec->searchTerm('a <-> b<2>c'));
    }

    function test_phrases_surrounded_by_quotation_marks_use_and_operators()
    {
        $this->assertSame('(a&b)', $this->spec->searchTerm('" a b "'));
    }

    function test_not_operator()
    {
        $this->assertSame('a|!b|!c', $this->spec->searchTerm('a !b ! c'));
    }

    function test_asterisk_operator()
    {
        $this->assertSame('abc:*', $this->spec->searchTerm('abc*'));
    }

    function test_useless_asterisks_are_removed()
    {
        $this->assertSame('abc:*', $this->spec->searchTerm('* *abc** **'));
    }

    function test_ampersands_and_parentheses_and_backslashes_and_colons_are_removed()
    {
        $this->assertSame('a|b|c', $this->spec->searchTerm('&a:: \\& (b: & c :::)& (\\\)'));
    }

    function test_useless_quotation_marks_are_removed()
    {
        $this->assertSame('(a&b)|c|d', $this->spec->searchTerm('"" "a b" " c d'));
    }
}
