<?php
/**
 * @author: James Murray <jaimz@vertigolabs.org>
 * @copyright:
 * @date: 9/15/2015
 * @time: 5:34 PM
 */

namespace TsVector\Fixture;

use Doctrine\ORM\Mapping\Column;
use Doctrine\ORM\Mapping\Entity;
use Doctrine\ORM\Mapping\GeneratedValue;
use Doctrine\ORM\Mapping\Id;
use Doctrine\ORM\Mapping\Table;
use VertigoLabs\DoctrineFullTextPostgres\ORM\Mapping\TsVector;

/**
 * Class Article
 * @package TsVector\Fixture
 * @Table(name="articles")
 * @Entity()
 */
class Article
{
	/**
  * @Id()
  * @GeneratedValue(strategy="IDENTITY")
  * @Column(name="id", type="integer", nullable=false)
  */
 private int $id;

	/**
  * @Column(name="title", type="string", nullable=false)
  */
 private string $title;

	/**
  * @TsVector(name="title_fts", fields={"title"}, weight="A")
  */
 private \VertigoLabs\DoctrineFullTextPostgres\DBAL\Types\TsVector $titleFTS;
	/**
  * @Column(name="body", type="text", nullable=true)
  */
 private string $body;

	/**
  * @TsVector(name="body_fts", fields={"body"})
  */
 private \VertigoLabs\DoctrineFullTextPostgres\DBAL\Types\TsVector $bodyFTS;

	/**
	 * @return string
	 */
	public function getTitle()
	{
		return $this->title;
	}

	/**
	 * @param string $title
	 *
	 * @return Article
	 */
	public function setTitle( $title )
	{
		$this->title = $title;

		return $this;
	}

	/**
	 * @return string
	 */
	public function getBody()
	{
		return $this->body;
	}

	/**
	 * @param string $body
	 *
	 * @return Article
	 */
	public function setBody( $body )
	{
		$this->body = $body;

		return $this;
	}
}