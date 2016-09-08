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
     * @var integer
     * @Id()
     * @Column(name="id", type="integer", nullable=false)
     */
    private $id;
    
    /**
     * @var string
     * @Column(type="string", nullable=true)
     */
    private $allDefaults;
    
    /**
     * @TsVector(fields={"allDefaults"})
     */
    private $allDefaultsFTS;
}
