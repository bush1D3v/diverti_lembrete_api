<?php

declare(strict_types=1);

namespace App\Domain\Reminder;

interface ReminderRepository
{
    /**
     * @return Reminder[]
     */
    public function findAll(int $limit = 0, int $offset = 10, string $emotion = null): array;

    /**
     * @param int $id
     * @return Reminder
     * @throws ReminderNotFoundException
     */
    public function findReminderOfId(int $id): Reminder;

    /**
     * @param Reminder $reminder
     * @return void
     */
    public function insert(array $reminder): int;

    /**
     * @param int $id
     * @return void
     * @throws ReminderNotFoundException
     */
    public function delete(int $id): void;
}
