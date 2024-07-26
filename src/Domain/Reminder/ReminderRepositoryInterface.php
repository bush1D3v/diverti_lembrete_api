<?php

declare(strict_types=1);

namespace App\Domain\Reminder;

interface ReminderRepositoryInterface
{
    /**
     * @param string $page
     * @param string $offset
     * @param string $limit
     * @param string $emotion
     * @param string $checked
     * @return Reminder[]
     * @throws ReminderNotFoundException
     */
    public function findAll(string $page = '1', string $offset = '0', string $limit = '10', string $emotion = null, string $checked = null): array;

    /**
     * @param string $id
     * @return Reminder
     * @throws ReminderNotFoundException
     */
    public function findById(string $id): Reminder;

    /**
     * @param Reminder $reminder
     * @return void
     */
    public function insert(array $reminder): string;

    /**
     * @param string $id
     * @return void
     * @throws ReminderNotFoundException
     */
    public function delete(string $id): void;

    /**
     * @param string $id
     * @return void
     * @throws ReminderNotFoundException
     */
    public function check(string $id): bool;

    /**
     * @return array
     */
    public function count(): array;
}
