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
        $offset = $queryParams['offset'] ?? '0';
        $limit = $queryParams['limit'] ?? '10';
        $page = $queryParams['page'] ?? '1';
        $emotion = $queryParams['emotion'] ?? null;
        $checked = $queryParams['checked'] ?? null;

        $reminders = $this->reminderRepository->findAll($page, $offset, $limit, $emotion, $checked);

        if (empty($reminders['reminders'])) {
            $this->logger->info("Nenhum lembrete foi encontrado.");

            throw new ReminderNotFoundException();
        }

        $this->logger->info("Lista de lembretes foi acessada.");

        return $this->respondWithData($reminders);
    }
}
