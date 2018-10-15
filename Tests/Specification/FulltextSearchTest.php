<?php
namespace Vanio\DomainBundle\Tests\Specifaction;

use PHPUnit\Framework\TestCase;
use Vanio\DomainBundle\Specification\FulltextSearch;
use Vanio\DomainBundle\Specification\TextSearchSpecification;

class FulltextSearchTest extends TestCase
{
    function test_search_term_is_trimmed()
    {
        $this->assertSame('abc', FulltextSearch::processSearchTerm(' abc  '));
    }

    function test_spaces_are_replaced_by_or_operators()
    {
        $this->assertSame('a|b|c', FulltextSearch::processSearchTerm(' a b     c '));
    }

    function test_follow_by_operator()
    {
        $this->assertSame('a<->b<2>c', FulltextSearch::processSearchTerm('a <-> b<2>c'));
    }

    function test_phrases_surrounded_by_quotation_marks_use_and_operators()
    {
        $this->assertSame('(a&b)', FulltextSearch::processSearchTerm('" a b "'));
    }

    function test_not_operator()
    {
        $this->assertSame('a|!b|!c', FulltextSearch::processSearchTerm('a !b ! c'));
    }

    function test_asterisk_operator()
    {
        $this->assertSame('abc:*', FulltextSearch::processSearchTerm('abc*'));
    }

    function test_useless_asterisks_are_removed()
    {
        $this->assertSame('abc:*', FulltextSearch::processSearchTerm('* *abc** **'));
    }

    function test_ampersands_and_parentheses_and_backslashes_and_colons_are_removed()
    {
        $this->assertSame('a|b|c', FulltextSearch::processSearchTerm('&a:: \\& (b: & c :::)& (\\\)'));
    }

    function test_useless_quotation_marks_are_removed()
    {
        $this->assertSame('(a&b)|c|d', FulltextSearch::processSearchTerm('"" "a b" " c d'));
    }

    function test_asterisk_combined_with_quotation_marks()
    {
        $this->assertSame('(a:*&b&c)', FulltextSearch::processSearchTerm('"a* b c"'));
        $this->assertSame('(a&b:*&c)', FulltextSearch::processSearchTerm('"a b* c"'));
        $this->assertSame('(a&b&c:*)', FulltextSearch::processSearchTerm('"a b c*"'));
        $this->assertSame('(a&b&c)', FulltextSearch::processSearchTerm('*"a b c"*'));
    }

    function test_some_no_so_usual_combinations()
    {
        $this->assertSame('(a:*&!b)', FulltextSearch::processSearchTerm('"a* !b"'));
        $this->assertSame('(a&!b:*)', FulltextSearch::processSearchTerm('"a !b*"'));
        $this->assertSame('!(a&b)', FulltextSearch::processSearchTerm('!"a b"'));
        $this->assertSame('(a:*&!b:*)', FulltextSearch::processSearchTerm('"a* !b*"'));
        $this->assertSame('(!a:*&!b:*)', FulltextSearch::processSearchTerm('"!a* !b*"'));
    }

    function test_some_really_weird_combinations()
    {
        $this->assertSame('!(!a:*&!b:*)', FulltextSearch::processSearchTerm('!"!a* !b*"'));
        $this->assertSame('!(!a:*&!b:*&de)|!fg:*', FulltextSearch::processSearchTerm('!"!a* !b*"* "de" !fg*!***'));
        $this->assertSame('a|(b&c)|d', FulltextSearch::processSearchTerm('a"b c"d'));
    }
}
