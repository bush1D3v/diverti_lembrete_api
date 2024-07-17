<?php

declare(strict_types=1);

use App\Domain\Reminder\ReminderRepository;
use App\Infrastructure\Persistence\Reminder\InMemoryReminderRepository;
use DI\ContainerBuilder;

return function (ContainerBuilder $containerBuilder) {
    $containerBuilder->addDefinitions([
        ReminderRepository::class => \DI\autowire(InMemoryReminderRepository::class),
    ]);
};
