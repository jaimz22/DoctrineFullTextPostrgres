<?php
/**
 * @author: James Murray <jaimz@vertigolabs.org>
 * @copyright:
 * @date: 9/15/2015
 * @time: 3:20 PM
 */

namespace VertigoLabs\DoctrineFullTextPostgres\ORM\Mapping;

use Doctrine\Common\Annotations\Annotation;
use Doctrine\Common\Annotations\Annotation\Target;

/**
 * Class TsVector
 * @package VertigoLabs\DoctrineFullTextPostgres\ORM\Mapping
 * @Annotation
 * @Target("PROPERTY")
 */
final class TsVector extends Annotation
{
	/**
	 * @var array<string>
	 * @Annotation\Required()
	 */
	public $fields = [];
	/**
	 * @var string
	 */
	public $name;
	/**
	 * @var string
	 * @Annotation\Enum({'A',"B","C","D"})
	 */
	public $weight = 'D';
	/**
	 * @var string
	 */
	public $language = 'english';
}
