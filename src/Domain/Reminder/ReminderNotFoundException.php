<?php

declare(strict_types=1);

namespace App\Domain\Reminder;

use App\Domain\DomainException\DomainRecordNotFoundException;

class ReminderNotFoundException extends DomainRecordNotFoundException
{
    public $message = 'O lembrete que você buscou não foi encontrado.';
}
