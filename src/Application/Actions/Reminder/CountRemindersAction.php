<?php

declare(strict_types=1);

namespace App\Application\Actions\Reminder;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

class CountRemindersAction extends ReminderAction
{
    /**
     * {@inheritdoc}
     */
    protected function action(Request $request, Response $response): Response
    {
        $responseBody = $this->reminderRepository->count();

        $this->logger->info("Contagem de lembretes concluída.");

        return $this->respondWithData($responseBody);
    }
}
