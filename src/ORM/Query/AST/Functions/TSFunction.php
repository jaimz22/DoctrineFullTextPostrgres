<?php
/**
 * @author: James Murray <jaimz@vertigolabs.org>
 * @copyright:
 * @date: 9/19/2015
 * @time: 7:35 PM
 */

namespace VertigoLabs\DoctrineFullTextPostgres\ORM\Query\AST\Functions;

use Doctrine\Common\Annotations\AnnotationReader;
use Doctrine\Common\Persistence\Mapping\ClassMetadata;
use Doctrine\ORM\Query\AST\Functions\FunctionNode;
use Doctrine\ORM\Query\AST\PathExpression;
use Doctrine\ORM\Query\Lexer;
use Doctrine\ORM\Query\Parser;
use Doctrine\ORM\Query\SqlWalker;
use VertigoLabs\DoctrineFullTextPostgres\ORM\Mapping\TsVector;

/**
 * Class TSFunction
 * @package VertigoLabs\DoctrineFullTextPostgres\ORM\Query\AST\Functions
 */
abstract class TSFunction extends FunctionNode
{
	/**
	 * @var PathExpression
	 */
	public $ftsField = null;
	/**
	 * @var PathExpression
	 */
	public $queryString = null;

	public function parse(Parser $parser)
	{
		$parser->match(Lexer::T_IDENTIFIER);
		$parser->match(Lexer::T_OPEN_PARENTHESIS);
		$this->ftsField = $parser->StringPrimary();
		$parser->match(Lexer::T_COMMA);
		$this->queryString = $parser->StringPrimary();
		$parser->match(Lexer::T_CLOSE_PARENTHESIS);
	}

	protected function findFTSField(SqlWalker $sqlWalker)
	{
		$reader = new AnnotationReader();
		$dqlAlias = $this->ftsField->identificationVariable;
		$class = $sqlWalker->getQueryComponent($dqlAlias);
		/** @var ClassMetadata $classMetaData */
		$classMetaData = $class['metadata'];
		$classRefl = $classMetaData->getReflectionClass();
		foreach($classRefl->getProperties() as $prop) {
			if($prop->name == $this->ftsField->field) {
				$this->ftsField->field = $prop->name;
				break;
			}

			/** @var TsVector $annot */
			$annot = $reader->getPropertyAnnotation($prop, TsVector::class);
			if (is_null($annot)) {
				continue;
			}
			if (in_array($this->ftsField->field,$annot->fields)) {
				$this->ftsField->field = $prop->name;
				break;
			}
		}
	}
}
