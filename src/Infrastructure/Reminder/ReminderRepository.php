<?php

declare(strict_types=1);

namespace App\Infrastructure\Reminder;

use Ramsey\Uuid\Uuid;
use App\Domain\Reminder\Reminder;
use App\Queue\Reminder\ReminderQueues;
use App\Json\Reminder\ReminderJsonFunctions;
use App\Domain\Reminder\ReminderNotFoundException;
use App\Domain\Reminder\ReminderRepositoryInterface;
use App\Infrastructure\Reminder\Dto\ReminderInitialDTO;

class ReminderRepository implements ReminderRepositoryInterface
{
    private const FILE_PATH = ReminderJsonFunctions::FILE_PATH;

    /**
     * @var ReminderJsonFunctions
     */
    private $jsonFunctions;

    /**
     * @var ReminderQueues
     */
    private $queue;

    /**
     * Loads reminder from a JSON file or initializes the file with default reminders.
     */
    public function __construct()
    {
        $this->jsonFunctions = new ReminderJsonFunctions();
        $this->queue = new ReminderQueues();
        if (!file_exists(self::FILE_PATH) || empty(file_get_contents(self::FILE_PATH))) {
            $defaultReminders = ReminderInitialDTO::getDefaultReminders();
            $this->jsonFunctions->writeRemindersToFile($defaultReminders);
        }
    }

    /**
     * Find all reminders.
     * 
     * {@inheritdoc}
     */
    public function findAll(string $page = '1', string $emotion = null, string $checked = null): array
    {
        $limit = 10;
        $offset = 0;

        if ($page > 1) {
            $offset = ($page - 1) * $limit;
        }

        $reminders = ReminderJsonFunctions::readRemindersFromFile();
        if ($emotion) {
            $reminders = array_filter($reminders, function (Reminder $reminder) use ($emotion) {
                return $reminder->getEmotion() === $emotion;
            });
        }

        if ($checked !== null) {
            $reminders = array_filter($reminders, function (Reminder $reminder) use ($checked) {
                $checkAsString = $reminder->getCheck() ? 'true' : 'false';
                return $checkAsString === $checked;
            });
        }

        $totalReminders = count($reminders);

        usort($reminders, function (Reminder $a, Reminder $b) {
            return $a->getDate() <=> $b->getDate();
        });

        $reminders = array_slice($reminders, $offset, $limit);

        return [
            'reminders' => array_values($reminders),
            'total' => $totalReminders,
        ];
    }

    /**
     * Find a reminder by its ID.
     * 
     * {@inheritdoc}
     */
    public function findById(string $id): Reminder
    {
        $reminders = $this->jsonFunctions->readRemindersFromFile();
        if (!isset($reminders[$id])) {
            throw new ReminderNotFoundException();
        }

        return $reminders[$id];
    }

    /**
     * Insert a new reminder into the JSON file.
     * 
     * {@inheritdoc}
     * 
     * @return int
     */
    public function insert(array $reminder): string
    {
        $reminderId = Uuid::uuid4()->toString();
        $reminderText = $reminder['text'];
        $reminderEmotion = $reminder['emotion'];
        $reminderDate =  new \DateTime($reminder['date']);
        $reminderCheck = false;
        $this->queue->sendReminder($reminderId, $reminderText, $reminderEmotion, $reminderDate, $reminderCheck);

        return $reminderId;
    }

    /**
     * Delete a reminder from the JSON file.
     * 
     * {@inheritdoc}
     */
    public function delete(string $id): void
    {
        $reminders = $this->jsonFunctions->readRemindersFromFile();
        if (!isset($reminders[$id])) {
            throw new ReminderNotFoundException();
        }

        unset($reminders[$id]);
        $this->jsonFunctions->writeRemindersToFile($reminders);
    }

    /**
     * Update the check status of a reminder.
     * 
     * {@inheritdoc}
     */
    public function check(string $id): bool
    {
        $reminders = $this->jsonFunctions->readRemindersFromFile();
        if (!isset($reminders[$id])) {
            throw new ReminderNotFoundException();
        }

        $reminders[$id]->setCheck(!$reminders[$id]->getCheck());
        $this->jsonFunctions->writeRemindersToFile($reminders);

        return $reminders[$id]->getCheck();
    }
}