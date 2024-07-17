<?php

declare(strict_types=1);

namespace App\Application\Actions\Reminder;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

class ViewReminderAction extends ReminderAction
{
    /**
     * {@inheritdoc}
     */
    protected function action(Request $request, Response $response): Response
    {
        $reminderId = (int) $this->resolveArg('id');
        $reminder = $this->reminderRepository->findReminderOfId($reminderId);

        $this->logger->info(sprintf("Lembrete do id %s foi acessado.", $reminderId));

        return $this->respondWithData($reminder);
    }
}
