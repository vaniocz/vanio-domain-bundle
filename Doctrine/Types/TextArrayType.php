<?php
namespace Vanio\DomainBundle\Doctrine\Types;

use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\DBAL\Types\Type;

class TextArrayType extends Type
{
    const NAME = 'text_array';

    /**
     * @param mixed[] $fieldDeclaration
     * @param AbstractPlatform $platform
     * @return string
     */
    public function getSQLDeclaration(array $fieldDeclaration, AbstractPlatform $platform): string
    {
        return 'TEXT[]';
    }

    /**
     * @param string[] $value
     * @param AbstractPlatform $platform
     * @return string|null
     * @phpcsSuppress SlevomatCodingStandard.TypeHints.TypeHintDeclaration.MissingParameterTypeHint
     */
    public function convertToDatabaseValue($value, AbstractPlatform $platform)
    {
        if ($value === null) {
            return null;
        } elseif ($value === []) {
            return '{}';
        }

        $result = '';

        foreach ($value as $element) {
            if ($element === null) {
                $result .= 'NULL,';
            } elseif ($element === '') {
                $result .= '"",';
            } else {
                $result .= sprintf('"%s",', addcslashes($element, '"'));
            }
        }

        return sprintf('{%s}', substr($result, 0, -1));
    }

    /**
     * @param string|null $value
     * @param AbstractPlatform $platform
     * @return string[]|null
     * @phpcsSuppress SlevomatCodingStandard.TypeHints.TypeHintDeclaration.MissingParameterTypeHint
     */
    public function convertToPHPValue($value, AbstractPlatform $platform)
    {
        if ($value === null) {
            return null;
        }

        $array = [];

        if ($value === '{}') {
            return $array;
        }

        preg_match_all(
            '/(?<=^\{|,)(([^,"{]*)|\s*"((?:[^"\\\\]|\\\\(?:.|[0-9]+|x[0-9a-f]+))*)"\s*)(,|(?<!^\{)(?=\}$))/i',
            $value,
            $matches,
            PREG_SET_ORDER
        );

        foreach ($matches as $match) {
            if ($match[3] === '') {
                $array[] = $match[2] === 'NULL' ? null : $match[2];
            } else {
                $array[] = stripcslashes($match[3]);
            }
        }

        return $array;
    }

    public function getName(): string
    {
        return self::NAME;
    }

    /**
     * @param AbstractPlatform $platform
     * @return string[]
     */
    public function getMappedDatabaseTypes(AbstractPlatform $platform): array
    {
        return ['text[]'];
    }
}
