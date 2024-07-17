<?php

declare(strict_types=1);

namespace App\Application\Actions\Reminder;

use App\Application\Actions\Action;
use App\Domain\Reminder\ReminderRepository;
use Psr\Log\LoggerInterface;

abstract class ReminderAction extends Action
{
    protected ReminderRepository $reminderRepository;

    public function __construct(LoggerInterface $logger, ReminderRepository $reminderRepository)
    {
        parent::__construct($logger);
        $this->reminderRepository = $reminderRepository;
    }
}
