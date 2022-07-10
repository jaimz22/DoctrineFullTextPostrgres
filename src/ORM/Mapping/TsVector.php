<?php
/**
 * @author: James Murray <jaimz@vertigolabs.org>
 * @copyright:
 * @date: 9/15/2015
 * @time: 3:20 PM
 */

namespace VertigoLabs\DoctrineFullTextPostgres\ORM\Mapping;

use Doctrine\ORM\Mapping\Annotation;
use Doctrine\Common\Annotations\Annotation\Target;
use Doctrine\Common\Annotations\Annotation\NamedArgumentConstructor;
use \Attribute;
/**
 * Class TsVector.
 *
 * @Annotation
 * @NamedArgumentConstructor()
 * @Target("PROPERTY")
 */
#[Attribute(Attribute::TARGET_PROPERTY)]
final class TsVector implements Annotation
{
    public function __construct(public string $name, public array $fields=[], public string $weight='D', public string $language = 'english')
    {
    }

    public function getWeight(): string
    {
        return $this->weight;
    }

    public function getLanguage(): string
    {
        return $this->language;
    }

}
