<?php
/**
 * @author: James Murray <jaimz@vertigolabs.org>
 * @copyright:
 * @date: 9/15/2015
 * @time: 4:32 PM
 */
define('TESTS_PATH',__DIR__);
define('TESTS_TEMP_PATH',__DIR__.'/temp');
define('VENDOR_PATH',__DIR__.'../vendor');

if (!class_exists(\PHPUnit\Framework\TestCase::class) || version_compare(\PHPUnit\Runner\Version::id(), '3.5') < 0) {
	die('PHPUnit framework 3.5 or newer is required');
}

if (!class_exists(\PHPUnit\Framework\MockObject\MockBuilder::class)) {
	die('PHPUnit MockObject Plugin 1.0.8 or newer is required');
}

/** @var \Composer\Autoload\ClassLoader $loader */
$loader = require __DIR__.'/../vendor/autoload.php';

$loader->add('VertigoLabs\\ORM\\Mapping\\Mock',__DIR__);
$loader->add('Base',__DIR__.'/VertigoLabs');

#fixtures
$loader->add('TsVector\\Fixture',__DIR__.'/VertigoLabs');

\Doctrine\Common\Annotations\AnnotationRegistry::registerLoader([$loader,'loadClass']);
\Doctrine\Common\Annotations\AnnotationRegistry::registerAutoloadNamespace("VertigoLabs\\DoctrineFullTextPostgres\\ORM\\Mapping\\");
\Doctrine\DBAL\Types\Type::addType('tsvector',\VertigoLabs\DoctrineFullTextPostgres\DBAL\Types\TsVector::class);

// auuto-loaded?
$reader = new \Doctrine\Common\Annotations\AnnotationReader();
//$reader = new \Doctrine\Common\Annotations\CachedReader($reader,new \Doctrine\Common\Cache\ArrayCache());
$_ENV['annotation_reader'] = $reader;
