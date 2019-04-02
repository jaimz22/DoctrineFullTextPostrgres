<?php
/**
 * @author: James Murray <jaimz@vertigolabs.org>
 * @copyright:
 * @date: 9/18/2015
 * @time: 3:12 PM
 */

namespace TsVector\Fixture;

use Doctrine\ORM\Mapping\Column;
use Doctrine\ORM\Mapping\Entity;
use Doctrine\ORM\Mapping\Id;
use VertigoLabs\DoctrineFullTextPostgres\ORM\Mapping\TsVector;

/**
 * Class MissingColumnEntity
 * @package VertigoLabs\TsVector\Fixture
 * @Entity()
 */
class GetterEntity
{
    /**
     * @var integer
     * @Id()
     * @Column(name="id", type="integer", nullable=false)
     */
    private $id;

    /**
     * @TsVector(fields={"calculateColumn"})
     */
    private $missingColumnFTS;

    public function getCalculateColumn()
    {

    }
}
