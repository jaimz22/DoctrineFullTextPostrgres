<?php
/**
 * @author: James Murray <jaimz@vertigolabs.org>
 * @copyright:
 * @date: 9/19/2015
 * @time: 10:18 AM
 */

namespace VertigoLabs\DoctrineFullTextPostgres\ORM\Query\AST\Functions;

use Doctrine\ORM\Query\SqlWalker;

/**
 * Class TsQueryFunction
 * @package VertigoLabs\DoctrineFullTextPostgres\ORM\Query\AST\Functions
 */
class TsQueryFunction extends TSFunction
{
	public function getSql(SqlWalker $sqlWalker)
	{
		$this->findFTSField($sqlWalker);
		return $this->ftsField->dispatch($sqlWalker).' @@ to_tsquery('.$this->queryString->dispatch($sqlWalker).')';
	}
}
