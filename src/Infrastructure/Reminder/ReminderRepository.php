<?php

declare(strict_types=1);

namespace App\Infrastructure\Reminder;

use Ramsey\Uuid\Uuid;
use App\Domain\Reminder\Reminder;
use App\Domain\Reminder\ReminderRepositoryInterface;
use App\Domain\Reminder\ReminderNotFoundException;
use PhpAmqpLib\Message\AMQPMessage;
use PhpAmqpLib\Connection\AMQPStreamConnection;

class ReminderRepository implements ReminderRepositoryInterface
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
                    (new \DateTime())->modify('+'.rand(1,24).' hours'),
                    false
                ),
                2 => new Reminder(
                    Uuid::uuid4()->toString(),
                    'Liberar minhas lágrimas tristes assim que eu entrar em casa 😢💔.',
                    'sad',
                    (new \DateTime())->modify('+'.rand(1,24).' hours'),
                    false
                ),
                3 => new Reminder(
                    Uuid::uuid4()->toString(),
                    '🔥💥 Quebrar meu videogame! Eu não aguento mais essa frustração! 💢😠',
                    'angry',
                    (new \DateTime())->modify('+'.rand(1,24).' hours'),
                    false
                ),
                4 => new Reminder(
                    Uuid::uuid4()->toString(),
                    'Tenho que verificar urgentemente a nota do Enem! 🤯📊 Ansiedade a mil,
                    mas preciso ver! 🔍',
                    'ansiety', 
                    (new \DateTime())->modify('+'.rand(1,24).' hours'),
                    false
                ),
                5 => new Reminder(
                    Uuid::uuid4()->toString(),
                    'Lembrar de convidar a Bruna para uma noite de puro glamour e sofisticação. ✨🍷🌹',
                    'luxury',
                    (new \DateTime())->modify('+'.rand(1,24).' hours'),
                    false
                ),
            ];
            $this->writeRemindersToFile($defaultReminders);
        }
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
    private function writeRemindersToFile(array $reminders): void
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

    private function sendReminder($id, $text, $emotion, $date, $check) {
        $queueHost = getenv('QUEUE_HOST') ?: $_ENV['QUEUE_HOST'];
        $queuePort = getenv('QUEUE_PORT') ?: $_ENV['QUEUE_PORT'];
        $queueUser = getenv('QUEUE_USER') ?: $_ENV['QUEUE_USER'];
        $queuePassword = getenv('QUEUE_PASSWORD') ?: $_ENV['QUEUE_PASSWORD'];
        $queueName = getenv('QUEUE_NAME') ?: $_ENV['QUEUE_NAME'];

        $connection = new AMQPStreamConnection($queueHost, $queuePort, $queueUser, $queuePassword);
        $channel = $connection->channel();

        $channel->queue_declare($queueName, false, false, false, false);

        $data = json_encode([
            'id' => $id,
            'text' => $text,
            'emotion' => $emotion,
            'date' => $date,
            'check' => $check
        ]);

        $msg = new AMQPMessage($data);
        $channel->basic_publish($msg, '', $queueName);

        $channel->close();
        $connection->close();
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

        $reminders = $this->readRemindersFromFile();
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
    public function insert(array $reminder): string
    {
        $reminderId = Uuid::uuid4()->toString();
        $reminderText = $reminder['text'];
        $reminderEmotion = $reminder['emotion'];
        $reminderDate =  new \DateTime($reminder['date']);
        $reminderCheck = false;
        $this->sendReminder($reminderId, $reminderText, $reminderEmotion, $reminderDate, $reminderCheck);

        return $reminderId;
    }

    /**
     * Delete a reminder from the JSON file.
     * 
     * {@inheritdoc}
     */
    public function delete(string $id): void
    {
        $reminders = $this->readRemindersFromFile();
        if (!isset($reminders[$id])) {
            throw new ReminderNotFoundException();
        }

        unset($reminders[$id]);
        $this->writeRemindersToFile($reminders);
    }

    /**
     * Update the check status of a reminder.
     * 
     * {@inheritdoc}
     */
    public function check(string $id): void
    {
        $reminders = $this->readRemindersFromFile();
        if (!isset($reminders[$id])) {
            throw new ReminderNotFoundException();
        }

        $reminders[$id]->setCheck(!$reminders[$id]->getCheck());
        $this->writeRemindersToFile($reminders);
    }

    public function processReminder() {
        $queueHost = getenv('QUEUE_HOST') ?: $_ENV['QUEUE_HOST'];
        $queuePort = getenv('QUEUE_PORT') ?: $_ENV['QUEUE_PORT'];
        $queueUser = getenv('QUEUE_USER') ?: $_ENV['QUEUE_USER'];
        $queuePassword = getenv('QUEUE_PASSWORD') ?: $_ENV['QUEUE_PASSWORD'];
        $queueName = getenv('QUEUE_NAME') ?: $_ENV['QUEUE_NAME'];

        $connection = new AMQPStreamConnection($queueHost, $queuePort, $queueUser, $queuePassword);
        $channel = $connection->channel();
    
        $channel->queue_declare($queueName, false, false, false, false);
        
        $callback = function($msg) {
            $data = json_decode($msg->body, true);

            $reminderId = $data['id'];  
            $reminderText = $data['text'];
            $reminderEmotion = $data['emotion'];
            $reminderDate = $data['date'];
            $reminderCheck = $data['check'];

            $processedText = $this->openAIReminderTextProcessor($reminderText, $reminderEmotion);
            
            $reminders = $this->readRemindersFromFile();
            $reminders[] = new Reminder(
                $reminderId,
                $processedText,
                $reminderEmotion,
                new \DateTime($reminderDate['date']),
                $reminderCheck
            );
            $this->writeRemindersToFile($reminders);
        };
    
        $channel->basic_consume('reminderQueue', '', false, true, false, false, $callback);
    
        while ($channel->is_consuming()) {
            $channel->wait();
        }
    
        $channel->close();
        $connection->close();
    }
}