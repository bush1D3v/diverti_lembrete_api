<?php

declare(strict_types=1);

use App\Domain\Reminder\ReminderRepositoryInterface;
use App\Infrastructure\Reminder\ReminderRepository;
use DI\ContainerBuilder;

return function (ContainerBuilder $containerBuilder) {
    $containerBuilder->addDefinitions([
        ReminderRepositoryInterface::class => \DI\autowire(ReminderRepository::class),
    ]);
};
