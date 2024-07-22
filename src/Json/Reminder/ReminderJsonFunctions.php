<?php

namespace App\Json\Reminder;

use App\Domain\Reminder\Reminder;

class ReminderJsonFunctions
{
    public const FILE_PATH = __DIR__ . '/reminders.json';

    /**
     * Reads reminders from the JSON file.
     *
     * @return Reminder[]
     */
    public static function readRemindersFromFile(): array
    {
        $jsonData = file_get_contents(self::FILE_PATH);
        $decodedData = json_decode($jsonData, true);
        $reminders = [];
        foreach ($decodedData as $reminder) {
            $dateObject = new \DateTime($reminder['date']['date']);
            
            $reminders[$reminder['id']] = new Reminder(
                $reminder['id'], $reminder['text'], $reminder['emotion'], $dateObject, $reminder['check']
            );
        }
        return $reminders;
    }

    /**
     * Writes reminders to the JSON file.
     *
     * @param Reminder[] $reminders
     */
    public static function writeRemindersToFile(array $reminders): void
    {
        $data = array_map(function (Reminder $reminder) {
            return [
                'id' => $reminder->getId(),
                'text' => $reminder->getText(),
                'emotion' => $reminder->getEmotion(),
                'date' => $reminder->getDate(),
                'check' => $reminder->getCheck()
            ];
        }, $reminders);

        file_put_contents(self::FILE_PATH, json_encode(array_values($data)));
    }
}