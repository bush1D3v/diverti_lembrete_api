<?php

declare(strict_types=1);

namespace App\Application\Actions\Reminder;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

class InsertReminderAction extends ReminderAction
{
    /**
     * {@inheritdoc}
     */
    protected function action(Request $request, Response $response): Response
    {
        $body = $request->getParsedBody();
        $reminderId = $this->reminderRepository->insert($body);

        $this->logger->info(sprintf("Lembrete do id %s foi inserido.", $reminderId));

        return $response->withHeader("Location", sprintf("/reminders/%s", $reminderId))->withStatus(201);
    }
}
