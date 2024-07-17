<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Reminder;

use Ramsey\Uuid\Uuid;
use App\Domain\Reminder\Reminder;
use App\Domain\Reminder\ReminderRepository;
use App\Domain\Reminder\ReminderNotFoundException;

class InMemoryReminderRepository implements ReminderRepository
{
    private const FILE_PATH = __DIR__ . '/reminders.json';

    /**
     * Loads reminder from a JSON file or initializes the file with default reminders.
     */
    public function __construct()
    {
        if (!file_exists(self::FILE_PATH) || empty(file_get_contents(self::FILE_PATH))) {
            $defaultReminders = [
                1 => new Reminder(
                    Uuid::uuid4()->toString(), 
                    '🌟✨ Não se esqueça de fazer carinho na Belinha hoje! Ela merece todo o nosso amor e alegria! 🐾❤️😊',
                    'happy',
                    (new \DateTime())->modify('+'.rand(1,24).' hours')
                ),
                2 => new Reminder(
                    Uuid::uuid4()->toString(),
                    'Lembre-se: Liberar minhas lágrimas tristes assim que eu entrar em casa 😢💔.',
                    'sad',
                    (new \DateTime())->modify('+'.rand(1,24).' hours')
                ),
                3 => new Reminder(
                    Uuid::uuid4()->toString(),
                    '🔥💥 Quebrar meu videogame! Eu não aguento mais essa frustração! 💢😠',
                    'angry',
                    (new \DateTime())->modify('+'.rand(1,24).' hours')
                ),
                4 => new Reminder(
                    Uuid::uuid4()->toString(),
                    '🔴 Lembrar: Tenho que verificar urgentemente a nota do Enem! 🤯📊 Ansiedade a mil,
                    mas preciso ver! 🔍',
                    'ansiety', 
                    (new \DateTime())->modify('+'.rand(1,24).' hours')
                ),
                5 => new Reminder(
                    Uuid::uuid4()->toString(),
                    'Lembrar de convidar a Bruna para uma noite de puro glamour e sofisticação. ✨🍷🌹',
                    'luxury',
                    (new \DateTime())->modify('+'.rand(1,24).' hours')
                ),
            ];
            $this->writeRemindersToFile($defaultReminders);
        }
    }

    /**
     * Find all reminders.
     * 
     * {@inheritdoc}
     */
    public function findAll(int $limit = 0, int $offset = 10, string $emotion = null): array
    {
        $reminders = $this->readRemindersFromFile();
        if ($emotion) {
            $reminders = array_filter($reminders, function (Reminder $reminder) use ($emotion) {
                return $reminder->getEmotion() === $emotion;
            });
        }
        $reminders = array_slice($reminders, $offset, $limit);

        return array_values($reminders);
    }

    /**
     * Find a reminder by its ID.
     * 
     * {@inheritdoc}
     */
    public function findReminderOfId(int $id): Reminder
    {
        $reminders = $this->readRemindersFromFile();
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
    public function insert(array $reminder): int
    {
        $reminderText = $this->openAIReminderTextProcessor($reminder['text'], $reminder['emotion']);

        $reminders = $this->readRemindersFromFile();
        $reminderId = max(array_keys($reminders)) + 1;
        $reminders[] = new Reminder(
            $reminderId,
            $reminderText,
            $reminder['emotion'],
            new \DateTime($reminder['date'])
        );
        $this->writeRemindersToFile($reminders);

        return $reminderId;
    }

    /**
     * Delete a reminder from the JSON file.
     * 
     * {@inheritdoc}
     */
    public function delete(int $id): void
    {
        $reminders = $this->readRemindersFromFile();
        if (!isset($reminders[$id])) {
            throw new ReminderNotFoundException();
        }

        unset($reminders[$id]);
        $this->writeRemindersToFile($reminders);
    }

    /**
     * Reads reminders from the JSON file.
     *
     * @return Reminder[]
     */
    private function readRemindersFromFile(): array
    {
        $jsonData = file_get_contents(self::FILE_PATH);
        $decodedData = json_decode($jsonData, true);
        $reminders = [];
        foreach ($decodedData as $reminder) {
            $dateObject = new \DateTime($reminder['date']['date']);
            
            $reminders[$reminder['id']] = new Reminder(
                $reminder['id'], $reminder['text'], $reminder['emotion'], $dateObject,
            );
        }
        return $reminders;
    }

    /**
     * Writes reminders to the JSON file.
     *
     * @param Reminder[] $reminders
     */
    private function writeRemindersToFile(array $reminders): void
    {
        $data = array_map(function (Reminder $reminder) {
            return [
                'id' => $reminder->getId(),
                'text' => $reminder->getText(),
                'emotion' => $reminder->getEmotion(),
                'date' => $reminder->getDate(),
            ];
        }, $reminders);

        file_put_contents(self::FILE_PATH, json_encode(array_values($data)));
    }

    /**
     * OpenAI text reminder processor based on the reminder emotion.
     *
     * @param string $text
     * @param string $emotion
     */
    private function openAIReminderTextProcessor(string $reminderText, string $reminderEmotion): string 
    {
        if (empty($reminderText)) throw new \InvalidArgumentException("O texto não pode estar vazio.");

        if (empty($reminderEmotion)) throw new \InvalidArgumentException("A emoção não pode estar vazia.");

        $apiKey = getenv('OPEN_AI_KEY') ?: $_ENV['OPEN_AI_KEY'];
        $prompt = sprintf(
            'Customize this reminder: "%s" based on this emotion: "%s". 
            I need a reminder with high emotional impact, including emojis in the reminder, not inserting this in the middle of the text.
            Just send me the reminder, excluding words derived from "remember". 
            Limited in 2x more letters than the original reminder, always in Portuguese.
            Reminder always in first person, not losing the textual context of being a reminder.', 
            $reminderText, $reminderEmotion
        );
    
        $ch = curl_init('https://api.openai.com/v1/chat/completions');
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode([
            'model' => 'gpt-4o', 'messages' => [
                ['role' => 'user', 'content' => $prompt]
            ]
        ]));
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-Type: application/json',
            "Authorization: Bearer $apiKey"
        ]);
    
        $response = curl_exec($ch);
        curl_close($ch);
    
        $responseData = json_decode($response, true);

        if (empty($responseData['choices'])) {
            throw new \Exception('Erro interno do servidor. Tente novamente mais tarde.');
        }

        $processedText = $responseData['choices'][0]['message']['content'];
        $processedText = stripslashes($processedText);
        $processedText = trim($processedText, "\"");

        return $processedText;
    }
}