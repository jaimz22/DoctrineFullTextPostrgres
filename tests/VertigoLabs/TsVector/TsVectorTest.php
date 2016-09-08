<?php
/**
 * @author: James Murray <jaimz@vertigolabs.org>
 * @copyright:
 * @date: 9/15/2015
 * @time: 5:15 PM
 */

namespace VertigoLabs\TsVector;

use Doctrine\Common\Annotations\AnnotationReader;
use Doctrine\Common\EventManager;
use Base\BaseORMTestCase;
use TsVector\Fixture\FullAnnotationsEntity;
use VertigoLabs\DoctrineFullTextPostgres\Common\TsVectorSubscriber;
use TsVector\Fixture\Article;
use TsVector\Fixture\DefaultAnnotationsEntity;
use TsVector\Fixture\MissingColumnEntity;
use TsVector\Fixture\WrongColumnTypeEntity;

class TsVectorTest extends BaseORMTestCase
{
    public function setUp()
    {
        parent::setUp();

        $evm = new EventManager();
        $evm->addEventSubscriber(new TsVectorSubscriber());
    }

    /**
     */
    public function shouldReceiveAnnotation()
    {
        $reader = new AnnotationReader();
        $refObj = new \ReflectionClass(Article::class);

        $titleProp = $refObj->getProperty('title');
        $bodyProp = $refObj->getProperty('body');
        $titleAnnotation = $reader->getPropertyAnnotation($titleProp, 'Vertigolabs\\DoctrineFullTextPostgres\\ORM\\Mapping\\TsVector');
        $bodyAnnotation = $reader->getPropertyAnnotation($bodyProp, 'Vertigolabs\\DoctrineFullTextPostgres\\ORM\\Mapping\\TsVector');

        static::assertNotNull($titleAnnotation, 'TsVector annotation not found for title');
        static::assertNotNull($bodyAnnotation, 'TsVector annotation not found for body');
    }

    /**
     * @test
     */
    public function shouldReceiveDefaults()
    {
        $metaData = $this->em->getClassMetadata(DefaultAnnotationsEntity::class);

        $allDefaultsMetadata = $metaData->getFieldMapping('allDefaultsFTS');

        static::assertEquals('allDefaultsFTS', $allDefaultsMetadata['fieldName']);
        static::assertEquals('allDefaultsFTS', $allDefaultsMetadata['columnName']);
        static::assertEquals('D', $allDefaultsMetadata['weight']);
        static::assertEquals('english', $allDefaultsMetadata['language']);
    }

    /**
     * @test
     */
    public function shouldReceiveCustom()
    {
        $metaData = $this->em->getClassMetadata(FullAnnotationsEntity::class);

        $allDefaultsMetadata = $metaData->getFieldMapping('allCustomFTS');

        static::assertEquals('allCustomFTS', $allDefaultsMetadata['fieldName']);
        static::assertEquals('fts_custom', $allDefaultsMetadata['columnName']);
        static::assertEquals('A', $allDefaultsMetadata['weight']);
        static::assertEquals('french', $allDefaultsMetadata['language']);
    }

    /**
     * @test
     * @expectedException \Doctrine\ORM\Mapping\MappingException
     * @expectedExceptionMessage Class does not contain missingColumn property
     */
    public function mustHaveColumn()
    {
        $metaData = $this->em->getClassMetadata(MissingColumnEntity::class);
    }

    /**
     * @test
     * @expectedException \Doctrine\Common\Annotations\AnnotationException
     * @expectedExceptionMessage TsVector\Fixture\WrongColumnTypeEntity::wrongColumnType TsVector field can only be assigned to String and Text columns. TsVector\Fixture\WrongColumnTypeEntity::wrongColumnType has the type integer
     */
    public function mustHaveCorrectColumnType()
    {
        $metaData = $this->em->getClassMetadata(WrongColumnTypeEntity::class);
    }

    /**
     * @test
     */
    public function shouldCreateSchema()
    {
        $classes = [
            $this->em->getClassMetadata(Article::class)
        ];
        $sql = $this->schemaTool->getCreateSchemaSql($classes);

        static::assertRegExp('/title_fts tsvector|body_fts tsvector/', $sql[0]);
    }

    /**
     * @test
     */
    public function shouldInsertData()
    {
        $this->setUpSchema([Article::class]);

        $article = new Article();
        $article->setTitle('test one');
        $article->setBody('This is test one');

        $this->em->persist($article);
        $this->em->flush();
    }

    /**
     * @test
     */
    public function shouldUpdateData()
    {
        $this->setUpSchema([Article::class]);

        $query = $this->em->createQuery('SELECT a FROM TsVector\\Fixture\\Article a WHERE tsquery(a.title'.',:searchQuery) = true');

        $article = new Article();
        $article->setTitle('test one');
        $article->setBody('empty');
        $this->em->persist($article);
        $this->em->flush();

        $query->setParameter('searchQuery', 'one');
        static::assertCount(1, $query->getArrayResult());
        $query->setParameter('searchQuery', 'two');
        static::assertCount(0, $query->getArrayResult());

        $article->setTitle('test two');
        $this->em->flush();

        $query->setParameter('searchQuery', 'one');
        static::assertCount(0, $query->getArrayResult());
        $query->setParameter('searchQuery', 'two');
        static::assertCount(1, $query->getArrayResult());
    }
}
