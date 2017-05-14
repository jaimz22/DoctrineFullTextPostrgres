<?php
/**
 * @author: James Murray <jaimz@vertigolabs.org>
 * @copyright:
 * @date: 9/15/2015
 * @time: 5:18 PM
 */

namespace VertigoLabs\DoctrineFullTextPostgres\Common;

use Doctrine\Common\Annotations\AnnotationException;
use Doctrine\Common\Annotations\AnnotationReader;
use Doctrine\Common\Annotations\AnnotationRegistry;
use Doctrine\Common\EventSubscriber;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\DBAL\Types\Type;
use Doctrine\ORM\Event\LoadClassMetadataEventArgs;
use Doctrine\ORM\Event\PreFlushEventArgs;
use Doctrine\ORM\Event\PreUpdateEventArgs;
use Doctrine\ORM\Events;
use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\ORM\Mapping\Column;
use Doctrine\ORM\Mapping\ManyToMany;
use Doctrine\ORM\Mapping\ManyToOne;
use Doctrine\ORM\Mapping\OneToMany;
use Doctrine\ORM\Mapping\OneToOne;
use Doctrine\ORM\Mapping\MappingException;
use VertigoLabs\DoctrineFullTextPostgres\ORM\Mapping\TsVector;
use \VertigoLabs\DoctrineFullTextPostgres\DBAL\Types\TsVector as TsVectorType;

/**
 * Class TsVectorSubscriber
 * @package VertigoLabs\DoctrineFullTextPostgres\Common
 */
class TsVectorSubscriber implements EventSubscriber
{
	const ANNOTATION_NS = 'VertigoLabs\\DoctrineFullTextPostgres\\ORM\\Mapping\\';
	const ANNOTATION_TSVECTOR = 'TsVector';

	private static $supportedTypes = [
		'string',
		'text',
		'array',
		'simple_array',
		'json',
		'json_array',
	];

	/**
	 * @var AnnotationReader
	 */
	private $reader;

	public function __construct()
	{
		AnnotationRegistry::registerAutoloadNamespace(self::ANNOTATION_NS);
		$this->reader = new AnnotationReader();

		if (!Type::hasType(strtolower(self::ANNOTATION_TSVECTOR))) {
			Type::addType(strtolower(self::ANNOTATION_TSVECTOR),TsVectorType::class);
		}
	}
	/**
	 * Returns an array of events this subscriber wants to listen to.
	 *
	 * @return array
	 */
	public function getSubscribedEvents()
	{
		return [
			Events::loadClassMetadata,
			Events::preFlush,
			Events::preUpdate,
		];
	}

	public function loadClassMetadata(LoadClassMetadataEventArgs $eventArgs)
	{
		/** @var ClassMetadata $metaData */
		$metaData = $eventArgs->getClassMetadata();

		$class = $metaData->getReflectionClass();
		foreach($class->getProperties() as $prop) {
			/** @var TsVector $annotation */
			$annotation = $this->reader->getPropertyAnnotation($prop, self::ANNOTATION_NS . self::ANNOTATION_TSVECTOR );
			if (is_null( $annotation ) ) {
				continue;
			}
			$this->checkWatchFields($class, $prop, $annotation);
			$metaData->mapField([
				'fieldName' => $prop->getName(),
				'columnName'=>$this->getColumnName($prop,$annotation),
				'type'=>'tsvector',
				'weight'=> strtoupper($annotation->weight),
				'language' => strtolower($annotation->language),
				'nullable' => $this->isWatchFieldNullable($class, $annotation)
			]);
		}
	}

	public function preFlush(PreFlushEventArgs $eventArgs)
	{
		$uow = $eventArgs->getEntityManager()->getUnitOfWork();
		$insertions = $uow->getScheduledEntityInsertions();
		$this->setTsVector($insertions);
	}

	public function preUpdate(PreUpdateEventArgs $eventArgs)
	{
		$uow = $eventArgs->getEntityManager()->getUnitOfWork();
		$updates = $uow->getScheduledEntityUpdates();
		$this->setTsVector($updates);
	}

	private function setTsVector($entities) {
		foreach($entities as $entity) {
			$refl = new \ReflectionObject($entity);
			foreach ($refl->getProperties() as $prop) {
				/** @var TsVector $annot */
				$annot = $this->reader->getPropertyAnnotation($prop, TsVector::class);
				if (is_null($annot)) {
					continue;
				}

				$fields = $annot->fields;
				$tsVectorVal = [];
				foreach($fields as $field) {
					$field = $refl->getProperty($field);
					$field->setAccessible(true);
					$test = $field->getName();
					$fieldValue = $field->getValue($entity);

					if($fieldValue instanceof ArrayCollection) {
						$fieldValue = $fieldValue->toArray();
					}					

					if (is_array($fieldValue)) {
						$fieldValue = implode(' ', $fieldValue);
					}
					$tsVectorVal[] = (string) $fieldValue;
				}
				$prop->setAccessible(true);
				$value = [
					'data'=>join(' ',$tsVectorVal),
					'language'=>$annot->language,
					'weight'=>$annot->weight
				];
				$prop->setValue($entity,$value);
			}
		}
	}

	private function getColumnName(\ReflectionProperty $property, TsVector $annotation)
	{
		$name = $annotation->name;
		if (is_null($name)) {
			$name = $property->getName();
		}
		return $name;
	}

	private function checkWatchFields(\ReflectionClass $class, \ReflectionProperty $targetProperty, TsVector $annotation)
	{
		foreach ($annotation->fields as $fieldName) {
			if (!$class->hasProperty($fieldName)) {
				throw new MappingException(sprintf('Class does not contain %s property',$fieldName));
			}
			$property = $class->getProperty($fieldName);
			$propAnnotations = $this->reader->getPropertyAnnotations($property);

			foreach($propAnnotations as $annotation) {
				$type = get_class($annotation);
				
				switch($type) {
					case Column::class:
						if (!in_array($annotation->type, self::$supportedTypes)) {
							throw new AnnotationException(sprintf(
								'%s::%s TsVector field can only be assigned to ( "%s" ) columns. %1$s::%s has the type %s',
								$class->getName(),
								$targetProperty->getName(),
								implode('" | "', self::$supportedTypes),
								$fieldName,
								$annotation->type
							));
						}
						break;
					case ManyToMany::class:
					case ManyToOne::class:
					case OneToMany::class:
					case OneToOne::class:
						$rc = new \ReflectionClass($annotation->targetEntity);
						if(!$rc->hasMethod('__toString')) {
							throw new AnnotationException(sprintf(
								'%s::%s TsVector field assigned to ( "%s" ) which does not implement __toString',
								$class->getName(),
								$targetProperty->getName(),
								$annotation->targetEntity
							));
						}
				}
			}
		}
	}

	private function isWatchFieldNullable(\ReflectionClass $class, TsVector $annotation)
	{
		foreach ($annotation->fields as $fieldName) {
			$property = $class->getProperty($fieldName);
			/** @var Column $propAnnot */
			$propAnnot = $this->reader->getPropertyAnnotation($property, Column::class );
			if ($propAnnot && $propAnnot->nullable === false) {
				return false;
			}
		}
		return true;
	}
}
