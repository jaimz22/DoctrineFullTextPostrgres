<?php
/**
 * @author: James Murray <jaimz@vertigolabs.org>
 * @copyright:
 * @date: 9/15/2015
 * @time: 3:12 PM
 */

namespace VertigoLabs\DoctrineFullTextPostgres\DBAL\Types;

use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\DBAL\Types\Type;

/**
 * Class TsVector.
 *
 * @todo figure out how to get the weight into the converted sql code
 */
class TsVector extends Type
{
    /**
     * Gets the SQL declaration snippet for a field of this type.
     *
     * @param array                                     $fieldDeclaration the field declaration
     * @param \Doctrine\DBAL\Platforms\AbstractPlatform $platform         the currently used database platform
     *
     * @return string
     */
    public function getSQLDeclaration(array $fieldDeclaration, AbstractPlatform $platform)
    {
        return 'tsvector';
    }

    public function canRequireSQLConversion(): bool
    {
        return true;
    }

    /**
     * Converts a value from its database representation to its PHP representation
     * of this type.
     *
     * @param mixed                                     $value    the value to convert
     * @param \Doctrine\DBAL\Platforms\AbstractPlatform $platform the currently used database platform
     *
     * @return mixed the PHP representation of the value
     */
    public function convertToPHPValue($value, AbstractPlatform $platform)
    {
        return $value;
    }

    /**
     * Converts a value from its PHP representation to its database representation
     * of this type.
     *
     * @param mixed                                     $value    the value to convert
     * @param \Doctrine\DBAL\Platforms\AbstractPlatform $platform the currently used database platform
     *
     * @return mixed the database representation of the value
     */
    public function convertToDatabaseValueSQL($sqlExp, AbstractPlatform $platform): string
    {
        return sprintf("to_tsvector('english', %s)", $sqlExp);
    }

    public function convertToDatabaseValue($value, AbstractPlatform $platform)
    {
        return $value['data'];
    }

    /**
     * Gets the name of this type.
     *
     * @return string
     */
    public function getName()
    {
        return 'tsvector';
    }

    public function getMappedDatabaseTypes(AbstractPlatform $platform)
    {
        return ['tsvector'];
    }
}
