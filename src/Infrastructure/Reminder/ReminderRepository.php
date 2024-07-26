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
    public function findAll(string $page = '1', string $offset = '0', string $limit = '10', string $emotion = null, string $checked = null): array
    {
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

        $reminders = array_slice($reminders, intval($offset), intval($limit));

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
        if (empty($reminderText)) throw new \InvalidArgumentException("O texto não pode estar vazio.");
        $reminderEmotion = $reminder['emotion'];
        if (empty($reminderEmotion)) throw new \InvalidArgumentException("A emoção não pode estar vazia.");
        $reminderDate =  new \DateTime($reminder['date']);
        if (empty($reminderDate)) throw new \InvalidArgumentException("A data não pode estar vazia.");
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

    /**
     * Count the total number of reminders and your derivated types (emotions, checks and unchecks).
     * 
     * {@inheritdoc}
     */
    public function count(): array
    {
        $reminders = $this->jsonFunctions->readRemindersFromFile();
        $totalReminders = count($reminders);
        $totalChecks = array_filter($reminders, function (Reminder $reminder) {
            return $reminder->getCheck();
        });
        $totalUnchecks = array_filter($reminders, function (Reminder $reminder) {
            return !$reminder->getCheck();
        });
        $totalHappiness = array_filter($reminders, function (Reminder $reminder) {
            return $reminder->getEmotion() === 'happiness';
        });
        $totalSad = array_filter($reminders, function (Reminder $reminder) {
            return $reminder->getEmotion() === 'sad';
        });
        $totalAngry = array_filter($reminders, function (Reminder $reminder) {
            return $reminder->getEmotion() === 'angry';
        });
        $totalAnxiety = array_filter($reminders, function (Reminder $reminder) {
            return $reminder->getEmotion() === 'anxiety';
        });
        $totalEnvy = array_filter($reminders, function (Reminder $reminder) {
            return $reminder->getEmotion() === 'envy';
        });
        $totalShame = array_filter($reminders, function (Reminder $reminder) {
            return $reminder->getEmotion() === 'shame';
        });
        $totalFear = array_filter($reminders, function (Reminder $reminder) {
            return $reminder->getEmotion() === 'fear';
        });
        $totalDisgust = array_filter($reminders, function (Reminder $reminder) {
            return $reminder->getEmotion() === 'disgust';
        });
        $totalBoredom = array_filter($reminders, function (Reminder $reminder) {
            return $reminder->getEmotion() === 'boredom';
        });

        return [
            'total' => $totalReminders,
            'checks' => count($totalChecks),
            'unchecks' => count($totalUnchecks),
            'happiness' => count($totalHappiness),
            'sad' => count($totalSad),
            'angry' => count($totalAngry),
            'anxiety' => count($totalAnxiety),
            'envy' => count($totalEnvy),
            'shame' => count($totalShame),
            'fear' => count($totalFear),
            'disgust' => count($totalDisgust),
            'boredom' => count($totalBoredom),
        ];
    }
}