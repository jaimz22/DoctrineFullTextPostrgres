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
use Doctrine\ORM\Event\PreUpdateEventArgs;
use Doctrine\ORM\Events;
use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\ORM\Mapping\Column;
use Doctrine\ORM\Mapping\MappingException;
use VertigoLabs\DoctrineFullTextPostgres\DBAL\Types\TsVector as TsVectorType;
use VertigoLabs\DoctrineFullTextPostgres\ORM\Mapping\TsVector;

/**
 * Class TsVectorSubscriber.
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
            Events::preFlush,
            Events::preUpdate,
        ];
    }

    public function loadClassMetadata(LoadClassMetadataEventArgs $eventArgs)
    {
        /** @var ClassMetadata $metaData */
        $metaData = $eventArgs->getClassMetadata();

        $class = $metaData->getReflectionClass();
        foreach ($class->getProperties() as $prop) {
            /** @var TsVector $annotation */
            $annotation = $this->reader->getPropertyAnnotation($prop, self::ANNOTATION_NS.self::ANNOTATION_TSVECTOR);
            if (null === $annotation) {
                continue;
            }
            $this->checkWatchFields($class, $prop, $annotation);
            $metaData->mapField([
                'fieldName' => $prop->getName(),
                'columnName' => $this->getColumnName($prop, $annotation),
                'type' => 'tsvector',
                'weight' => strtoupper($annotation->weight),
                'language' => strtolower($annotation->language),
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

    private function setTsVector($entities)
    {
        foreach ($entities as $entity) {
            $refl = new \ReflectionObject($entity);
            foreach ($refl->getProperties() as $prop) {
                /** @var TsVector $annot */
                $annot = $this->reader->getPropertyAnnotation($prop, TsVector::class);
                if (null === $annot) {
                    continue;
                }

                $fields = $annot->fields;
                $tsVectorVal = [];
                foreach ($fields as $field) {
                    if ($refl->hasMethod($field)) {
                        $method = $refl->getMethod($field);
                        $method->setAccessible(true);
                        $methodValue = $method->invoke($entity);
                        if (is_array($methodValue)) {
                            $methodValue = implode(' ', $methodValue);
                        }
                        $tsVectorVal[] = $methodValue;
                    }
                    if ($refl->hasProperty($field)) {
                        $field = $refl->getProperty($field);
                        $field->setAccessible(true);
                        $fieldValue = $field->getValue($entity);
                        if (is_array($fieldValue)) {
                            $fieldValue = implode(' ', $fieldValue);
                        }
                        $tsVectorVal[] = $fieldValue;
                    }
                }
                $prop->setAccessible(true);
                $value = [
                    'data' => join(' ', $tsVectorVal),
                    'language' => $annot->language,
                    'weight' => $annot->weight,
                ];
                $prop->setValue($entity, $value);
            }
        }
    }

    private function getColumnName(\ReflectionProperty $property, TsVector $annotation)
    {
        $name = $annotation->name;
        if (null === $name) {
            $name = $property->getName();
        }

        return $name;
    }

    private function checkWatchFields(\ReflectionClass $class, \ReflectionProperty $targetProperty, TsVector $annotation)
    {
        foreach ($annotation->fields as $fieldName) {
            if ($class->hasMethod($fieldName)) {
                continue;
            }

            if (!$class->hasProperty($fieldName)) {
                throw new MappingException(sprintf('Class does not contain %s property or getter', $fieldName));
            }

            $property = $class->getProperty($fieldName);
            /** @var Column $propAnnot */
            $propAnnot = $this->reader->getPropertyAnnotation($property, Column::class);
            if (!in_array($propAnnot->type, self::$supportedTypes)) {
                throw new AnnotationException(sprintf(
                    '%s::%s TsVector field can only be assigned to ( "%s" ) columns. %1$s::%s has the type %s',
                    $class->getName(),
                    $targetProperty->getName(),
                    implode('" | "', self::$supportedTypes),
                    $fieldName,
                    $propAnnot->type
                ));
            }
        }
    }

    private function isWatchFieldNullable(\ReflectionClass $class, TsVector $annotation)
    {
        foreach ($annotation->fields as $fieldName) {
            $property = $class->getProperty($fieldName);
            /** @var Column $propAnnot */
            $propAnnot = $this->reader->getPropertyAnnotation($property, Column::class);
            if (false === $propAnnot->nullable) {
                return false;
            }
        }

        return true;
    }
}
