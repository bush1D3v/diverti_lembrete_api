<?php

namespace App\Queue\Reminder;

require __DIR__ . '/../../../vendor/autoload.php';

$dotenv = \Dotenv\Dotenv::createImmutable(__DIR__ . '/../../../')->load();

use App\Queue\Reminder\ReminderQueues;

$task = new ReminderQueues();
$task->processReminder();