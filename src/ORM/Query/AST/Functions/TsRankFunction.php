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
 * Class TsRankFunction
 * @package VertigoLabs\DoctrineFullTextPostgres\ORM\Query\AST\Functions
 */
class TsRankFunction extends TSFunction
{
    public function getSql(SqlWalker $sqlWalker)
    {
        $this->findFTSField($sqlWalker);
        return 'ts_rank('.$this->ftsField->dispatch($sqlWalker).', to_tsquery('.$this->queryString->dispatch($sqlWalker).'))';
    }
}
