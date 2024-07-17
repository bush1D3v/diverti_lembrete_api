<?php

declare(strict_types=1);

namespace App\Application\Actions\Reminder;

use App\Domain\Reminder\ReminderNotFoundException;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

class ListRemindersAction extends ReminderAction
{
    /**
     * {@inheritdoc}
     */
    protected function action(Request $request, Response $response): Response
    {
        $queryParams = $request->getQueryParams();
        $limit = isset($queryParams['limit']) ? (int) $queryParams['limit'] : 10; 
        $offset = isset($queryParams['offset']) ? (int) $queryParams['offset'] : 0;
        $emotion = $queryParams['emotion'] ?? null;

        $reminders = $this->reminderRepository->findAll($limit, $offset, $emotion);

        if (empty($reminders)) {
            $this->logger->info("Nenhum lembrete foi encontrado.");

            throw new ReminderNotFoundException();
        }

        $this->logger->info("Lista de lembretes foi acessada.");

        return $this->respondWithData($reminders);
    }
}
