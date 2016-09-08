<?php
/**
 * @author: James Murray <jaimz@vertigolabs.org>
 * @copyright:
 * @date: 9/15/2015
 * @time: 5:12 PM
 */

namespace Base;

use Doctrine\Common\Persistence\PersistentObject;
use Doctrine\DBAL\Types\Type;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Tools\SchemaTool;
use Doctrine\ORM\Tools\Setup;
use VertigoLabs\DoctrineFullTextPostgres\Common\TsVectorSubscriber;
use VertigoLabs\DoctrineFullTextPostgres\ORM\Query\AST\Functions\TsQueryFunction;
use VertigoLabs\DoctrineFullTextPostgres\ORM\Query\AST\Functions\TsRankCDFunction;
use VertigoLabs\DoctrineFullTextPostgres\ORM\Query\AST\Functions\TsRankFunction;

class BaseORMTestCase extends \PHPUnit_Framework_TestCase
{
    /**
     * @var EntityManager
     */
    protected $em;
    /**
     * @var SchemaTool
     */
    protected $schemaTool;

    protected $createdSchemas = [];

    public function setUp()
    {
        $this->setUpDatabase();
        $this->setUpListeners();
    }

    public function setUpDatabase()
    {
        $isDevMode = true;
        $doctrineConfig = Setup::createAnnotationMetadataConfiguration([
            __DIR__ . '../TsVector/Fixture'
        ], $isDevMode);
        $doctrineConfig->addCustomStringFunction('tsquery', TsQueryFunction::class);
        $doctrineConfig->addCustomStringFunction('tsrank', TsRankFunction::class);
        $doctrineConfig->addCustomStringFunction('tsrankcd', TsRankCDFunction::class);

        $dbConfig = [
            'host' => 'localhost',
            'user' => 'postgres',
            'dbname' => 'ts_vector_test',
            'driver' => 'pdo_pgsql'
        ];

        $this->em = EntityManager::create($dbConfig, $doctrineConfig);
        $this->em->getConnection()->getDatabasePlatform()->registerDoctrineTypeMapping('tsvector', 'tsvector');
        $this->schemaTool = new SchemaTool($this->em);
        PersistentObject::setObjectManager($this->em);
    }

    public function setUpSchema(array $classes)
    {
        foreach ($classes as $k => $class) {
            $classes[$k] = $this->em->getClassMetadata($class);
        }
        $this->dropSchemas($classes);
        $this->createdSchemas = array_merge($this->createdSchemas, $classes);
        $this->schemaTool->createSchema($classes);
    }

    public function setUpListeners()
    {
        $this->em->getEventManager()->addEventSubscriber(new TsVectorSubscriber());
    }

    public function tearDown()
    {
        if (count($this->createdSchemas) > 0) {
            $this->dropSchemas($this->createdSchemas);
        }
    }

    private function dropSchemas($classes)
    {
        $this->schemaTool->dropSchema($classes);
    }
}
