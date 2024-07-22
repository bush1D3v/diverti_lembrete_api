<?php

namespace App\Queue\Reminder;

use App\Queue\Reminder\ReminderQueues;

$task = new ReminderQueues();
$task->processReminder();