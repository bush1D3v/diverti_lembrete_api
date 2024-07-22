<?php

declare(strict_types=1);

namespace App\Application\Actions\Reminder;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

class CheckRemindersAction extends ReminderAction
{
    /**
     * {@inheritdoc}
     */
    protected function action(Request $request, Response $response): Response
    {
        $reminderId = (string) $this->resolveArg('id');
        $check = $this->reminderRepository->check($reminderId);

        $this->logger->info(sprintf("Lembrete do id %s teve o estado de 'check' atualizado.", $reminderId));

        return $this->respondWithData($check);
    }
}