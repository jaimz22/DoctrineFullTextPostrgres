<?php
/**
 * @author: James Murray <jaimz@vertigolabs.org>
 * @copyright:
 * @date: 9/19/2015
 * @time: 7:25 PM
 */

namespace VertigoLabs\DoctrineFullTextPostgres\ORM\Query\AST\Functions;

use Doctrine\ORM\Query\SqlWalker;

/**
 * Class TsRankCDFunction
 * @package VertigoLabs\DoctrineFullTextPostgres\ORM\Query\AST\Functions
 */
class TsRankCDFunction extends TSFunction
{
	public function getSql(SqlWalker $sqlWalker)
	{
		$this->findFTSField($sqlWalker);
		return 'ts_rank_cd('.$this->ftsField->dispatch($sqlWalker).', to_tsquery('.$this->queryString->dispatch($sqlWalker).'))';
	}
}
