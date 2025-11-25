<?php

declare(strict_types=1);

use Rector\Config\RectorConfig;
use \Rector\Php84\Rector\Param\ExplicitNullableParamTypeRector;
use Rector\ValueObject\PhpVersion;

return static function (RectorConfig $rectorConfig): void {
    $rectorConfig->paths([
        __DIR__ . '/src',
    ]);

    // declare the codebase as PHP 8.4
    $rectorConfig->phpVersion(PhpVersion::PHP_84);

    $rectorConfig->rules([
        ExplicitNullableParamTypeRector::class,
    ]);
};
