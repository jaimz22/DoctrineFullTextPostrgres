<?php

namespace VertigoLabs\DoctrineFullTextPostgres\ORM\Query\AST\Functions;

use Doctrine\ORM\Query\SqlWalker;

/**
 * Class TsPlainQueryFunction.
 */
class TsPlainQueryFunction extends TSFunction
{
    public function getSql(SqlWalker $sqlWalker)
    {
        $this->findFTSField($sqlWalker);

        return $this->ftsField->dispatch($sqlWalker).' @@ plainto_tsquery('.$this->queryString->dispatch($sqlWalker).')';
    }
}
