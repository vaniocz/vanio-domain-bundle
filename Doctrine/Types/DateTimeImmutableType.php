<?php
namespace Vanio\DomainBundle\Doctrine\Types;

use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\DBAL\Types\ConversionException;
use Doctrine\DBAL\Types\Type;

class DateTimeImmutableType extends Type
{
    const NAME = 'datetime_immutable';

    /**
     * @param \DateTimeInterface|null $value
     * @param AbstractPlatform $platform
     * @return string|null
     */
    public function convertToDatabaseValue($value, AbstractPlatform $platform)
    {
        return $value ? $value->format($platform->getDateTimeFormatString()) : null;
    }

    /**
     * @param string|null $value
     * @param AbstractPlatform $platform
     * @return \DateTimeImmutable|null
     * @throws ConversionException
     */
    public function convertToPHPValue($value, AbstractPlatform $platform)
    {
        if ($value === null) {
            return $value;
        } elseif (!$dateTime = \DateTimeImmutable::createFromFormat($platform->getDateTimeFormatString(), $value)) {
            $dateTime = date_create_immutable($value);
        }

        if (!$dateTime) {
            throw ConversionException::conversionFailedFormat(
                $value,
                $this->getName(),
                $platform->getDateTimeFormatString()
            );
        }

        return $dateTime;
    }

    public function getSQLDeclaration(array $fieldDeclaration, AbstractPlatform $platform): string
    {
        return $platform->getDateTimeTypeDeclarationSQL($fieldDeclaration);
    }

    public function getName(): string
    {
        return self::NAME;
    }

    public function requiresSQLCommentHint(AbstractPlatform $platform): bool
    {
        return true;
    }
}
