<?php

declare(strict_types=1);

use Rector\Core\Configuration\Option;
use Rector\Php74\Rector\Property\TypedPropertyRector;
use Rector\Set\ValueObject\LevelSetList;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;
use Rector\PHPUnit\Set\PHPUnitSetList;

return static function (ContainerConfigurator $containerConfigurator): void {
    // get parameters
    $parameters = $containerConfigurator->parameters();
    $parameters->set(Option::PATHS, [
        __DIR__ . '/tests',
        __DIR__ . '/src',
    ]);

    // Define what rule sets will be applied

    // rector.php
//    $containerConfigurator->import(LevelSetList::UP_TO_PHP_74);
    $containerConfigurator->import(PHPUnitSetList::PHPUNIT_80);

    // get services (needed for register a single rule)
    // $services = $containerConfigurator->services();

    // register a single rule
    // $services->set(TypedPropertyRector::class);
};
