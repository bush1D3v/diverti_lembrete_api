<?php

declare(strict_types=1);

namespace App\Application\Actions\Reminder;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

class DeleteReminderAction extends ReminderAction
{
    /**
     * {@inheritdoc}
     */
    protected function action(Request $request, Response $response): Response
    {
        $reminderId = (int) $this->resolveArg('id');
        $this->reminderRepository->delete($reminderId);

        $this->logger->info(sprintf("Lembrete do id %s foi deletado.", $reminderId));

        return $response->withStatus(200);
    }
}
