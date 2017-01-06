<?php
namespace Vanio\DomainBundle\Specification;

/**
 * Base class for all text search specifications.
 */
abstract class TextSearchSpecification
{
    /**
     * Add/transform search operators and escape special characters.
     *
     * @param string $searchTerm
     *
     * @return string
     */
    protected function processSearchTerm(string $searchTerm): string
    {
        static $firstGroup = [
            '~[()&\\\:]+|^!+$|!+$|[&|*:]+!+|!+[&|*:]+$|(?:^|\s+)\*+~' => '',
            '~!\*|!+\s*~' => '!',
            '~"+\*+|"+~' => '"',
        ];
        $secondGroup = [
            '~"(.+)"~' => function (array $match): string {
                return '(' . trim(preg_replace('~\s+~', '&', trim($match[1])), '&') . ')';
            },
        ];
        static $thirdGroup = [
            '~"|\(\s*\)~' => '',
            '~\s*\<([\-0-9]{1,1})\>\s*~' => '<\1>',
            '~([^*]+)\*+~' => '\1:*',
            '~[\s|]+~' => '|',
        ];

        $result = preg_replace(array_keys($firstGroup), $firstGroup, trim($searchTerm));
        $result = preg_replace_callback_array($secondGroup, $result);
        $result = trim(preg_replace(array_keys($thirdGroup), $thirdGroup, $result), '|');

        return $result;
    }
}
