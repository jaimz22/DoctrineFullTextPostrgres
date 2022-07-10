<?php
/**
 * @author: James Murray <jaimz@vertigolabs.org>
 * @copyright:
 * @date: 9/16/2015
 * @time: 11:26 AM
 */

namespace TsVector\Fixture;

use Doctrine\ORM\Mapping\Column;
use Doctrine\ORM\Mapping\Entity;
use Doctrine\ORM\Mapping\Id;
use VertigoLabs\DoctrineFullTextPostgres\ORM\Mapping\TsVector;

/**
 * Class AllDefaults
 * @package VertigoLabs\TsVector\Fixture
 * @Entity()
 */
class DefaultAnnotationsEntity
{
	/**
  * @Id()
  * @Column(name="id", type="integer", nullable=false)
  */
 private int $id;
	/**
  * @Column(type="string", nullable=true)
  */
 private string $allDefaults;
	/**
	 * @TsVector(fields={"allDefaults"})
	 */
	private $allDefaultsFTS;
}