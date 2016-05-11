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
use Doctrine\DBAL\Types\Type;
use Doctrine\ORM\Event\LoadClassMetadataEventArgs;
use Doctrine\ORM\Event\PreFlushEventArgs;
use Doctrine\ORM\Events;
use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\ORM\Mapping\Column;
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

    /**
     * @var AnnotationReader
     */
    private $reader;

    public function __construct()
    {
        AnnotationRegistry::registerAutoloadNamespace(self::ANNOTATION_NS);
        $this->reader = new AnnotationReader();

        if (!Type::hasType(strtolower(self::ANNOTATION_TSVECTOR))) {
            Type::addType(strtolower(self::ANNOTATION_TSVECTOR), TsVectorType::class);
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
            Events::preFlush
        ];
    }

    public function loadClassMetadata(LoadClassMetadataEventArgs $eventArgs)
    {
        /** @var ClassMetadata $metaData */
        $metaData = $eventArgs->getClassMetadata();

        $class = $metaData->getReflectionClass();
        foreach ($class->getProperties() as $prop) {
            /** @var TsVector $annotation */
            $annotation = $this->reader->getPropertyAnnotation($prop, self::ANNOTATION_NS . self::ANNOTATION_TSVECTOR);
            if (is_null($annotation)) {
                continue;
            }
            $this->checkWatchFields($class, $annotation);
            $metaData->mapField([
                'fieldName' => $prop->getName(),
                'columnName' => $this->getColumnName($prop, $annotation),
                'type' => 'tsvector',
                'weight' => strtoupper($annotation->weight),
                'language' => strtolower($annotation->language),
                'nullable' => $this->isWatchFieldNullable($class, $annotation)
            ]);
        }
    }

    public function preFlush(PreFlushEventArgs $eventArgs)
    {
        $uow = $eventArgs->getEntityManager()->getUnitOfWork();
        $uow->computeChangeSets();
        $insertions = $uow->getScheduledEntityInsertions();
        $updates = $uow->getScheduledEntityUpdates();
        $entities = array_merge($insertions, $updates);

        foreach ($entities as $entity) {
            $refl = new \ReflectionObject($entity);
            foreach ($refl->getProperties() as $prop) {
                /** @var TsVector $annot */
                $annot = $this->reader->getPropertyAnnotation($prop, TsVector::class);
                if (is_null($annot)) {
                    continue;
                }

                $fields = $annot->fields;
                $tsVectorVal = [];
                foreach ($fields as $field) {
                    $field = $refl->getProperty($field);
                    $field->setAccessible(true);
                    $tsVectorVal[] = $field->getValue($entity);
                }
                $prop->setAccessible(true);
                $value = [
                    'data' => explode(' ', $tsVectorVal),
                    'language' => $annot->language,
                    'weight' => $annot->weight
                ];
                $prop->setValue($entity, $value);
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

    private function checkWatchFields(\ReflectionClass $class, TsVector $annotation)
    {
        foreach ($annotation->fields as $fieldName) {
            if (!$class->hasProperty($fieldName)) {
                throw new MappingException(sprintf('Class does not contain %s property', $fieldName));
            }
            $property = $class->getProperty($fieldName);
            /** @var Column $propAnnot */
            $propAnnot = $this->reader->getPropertyAnnotation($property, Column::class);
            if (!in_array($propAnnot->type, ['string', 'text'])) {
                throw new AnnotationException(sprintf('%s::%s TsVector field can only be assigned to String and Text columns. %s::%s has the type %s',
                    $class->getName(), $fieldName, $class->getName(), $property->getName(), $propAnnot->type));
            }
        }
    }

    private function isWatchFieldNullable(\ReflectionClass $class, TsVector $annotation)
    {
        foreach ($annotation->fields as $fieldName) {
            $property = $class->getProperty($fieldName);
            /** @var Column $propAnnot */
            $propAnnot = $this->reader->getPropertyAnnotation($property, Column::class);
            if ($propAnnot->nullable === false) {
                return false;
            }
        }
        return true;
    }
}
