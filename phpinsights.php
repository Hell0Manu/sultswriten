<?php

declare(strict_types=1);

return [
    'preset' => 'wordpress',
    'exclude' => [
        'vendor',
        'tests',
        'docs',
    ],
    'add' => [
    ],
    'remove' => [
    ],
    'config' => [
        \NunoMaduro\PhpInsights\Domain\Insights\ForbiddenFinalClasses::class => [
            'exclude' => [
                \Sults\Writen\Core\Container::class,
            ],
        ],
    ],
    'threads' => 1,
];
