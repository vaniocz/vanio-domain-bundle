<?php
namespace Vanio\DomainBundle\Doctrine\Types;

use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\DBAL\Platforms\PostgreSqlPlatform;
use Doctrine\DBAL\Types\ConversionException;
use Doctrine\DBAL\Types\Type;
use Ramsey\Uuid\Uuid;
use Ramsey\Uuid\UuidInterface;

class UuidArrayType extends Type
{
    const NAME = 'uuid_array';

    /**
     * @param mixed[] $fieldDeclaration
     * @param AbstractPlatform $platform
     */
    public function getSQLDeclaration(array $fieldDeclaration, AbstractPlatform $platform)
    {
        return $platform instanceof PostgreSqlPlatform
            ? sprintf('%s[]', $platform->getGuidTypeDeclarationSQL($fieldDeclaration))
            : $platform->getJsonTypeDeclarationSQL($fieldDeclaration);
    }

    /**
     * @param string|null $value
     * @param AbstractPlatform $platform
     * @return Uuid[]|null
     */
    public function convertToPHPValue($value, AbstractPlatform $platform)
    {
        if (!$value) {
            return null;
        }

        $uuids = [];
        $values = $platform instanceof PostgreSqlPlatform
            ? array_filter(explode(',', substr($value, 1, -1)))
            : json_decode($value, true);

        foreach ($values as $value) {
            try {
                $uuids[] = Uuid::fromString($value);
            } catch (InvalidArgumentException $e) {
                throw ConversionException::conversionFailed($value, static::NAME);
            }
        }

        return $uuids;
    }

    /**
     * @param UuidInterface[]|null $value
     * @param AbstractPlatform $platform
     * @return string|null
     */
    public function convertToDatabaseValue($value, AbstractPlatform $platform)
    {
        if ($value === null) {
            return null;
        }

        $uuids = [];

        foreach ($value as $uuid) {
            if (!$uuid instanceof UuidInterface || !Uuid::isValid($uuid)) {
                throw ConversionException::conversionFailed($uuid, static::NAME);
            }

            $uuids[] = (string) $uuid;
        }
        return $platform instanceof PostgreSqlPlatform
            ? sprintf('{%s}', implode(',', $uuids))
            : json_encode($uuids);
    }

    public function getName(): string
    {
        return static::NAME;
    }

    public function requiresSQLCommentHint(AbstractPlatform $platform): bool
    {
        return true;
    }
}
