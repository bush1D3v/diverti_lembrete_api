<?php

namespace App\Queue\Reminder;

use App\Domain\Reminder\Reminder;
use PhpAmqpLib\Message\AMQPMessage;
use PhpAmqpLib\Connection\AMQPStreamConnection;
use App\Json\Reminder\ReminderJsonFunctions;
use App\Infrastructure\Reminder\Openai\ReminderTextProcessor;

class ReminderQueues
{
    private $queueHost;
    private $queuePort;
    private $queueUser;
    private $queuePassword;
    private $queueName;

    /**
     * @var ReminderJsonFunctions
     */
    private $jsonFunctions;

    /**
     * @var ReminderTextProcessor
     */
    private $openAiFunctions;

    public function __construct()
    {
        $this->queueHost = getenv('QUEUE_HOST') ?: $_ENV['QUEUE_HOST'];
        $this->queuePort = getenv('QUEUE_PORT') ?: $_ENV['QUEUE_PORT'];
        $this->queueUser = getenv('QUEUE_USER') ?: $_ENV['QUEUE_USER'];
        $this->queuePassword = getenv('QUEUE_PASSWORD') ?: $_ENV['QUEUE_PASSWORD'];
        $this->queueName = getenv('QUEUE_NAME') ?: $_ENV['QUEUE_NAME'];
        $this->jsonFunctions = new ReminderJsonFunctions();
        $this->openAiFunctions = new ReminderTextProcessor();
    }

    public function sendReminder($id, $text, $emotion, $date, $check) 
    {
        $connection = new AMQPStreamConnection($this->queueHost, $this->queuePort, $this->queueUser, $this->queuePassword);
        $channel = $connection->channel();

        $channel->queue_declare($this->queueName, false, false, false, false);

        $data = json_encode([
            'id' => $id,
            'text' => $text,
            'emotion' => $emotion,
            'date' => $date,
            'check' => $check
        ]);

        $msg = new AMQPMessage($data);
        $channel->basic_publish($msg, '', $this->queueName);

        $channel->close();
        $connection->close();
    }

    public function processReminder() 
    {
        $connection = new AMQPStreamConnection($this->queueHost, $this->queuePort, $this->queueUser, $this->queuePassword);
        $channel = $connection->channel();
    
        $channel->queue_declare($this->queueName, false, false, false, false);
        
        $callback = function($msg) {
            $data = json_decode($msg->body, true);

            $reminderId = $data['id'];  
            $reminderText = $data['text'];
            $reminderEmotion = $data['emotion'];
            $reminderDate = $data['date'];
            $reminderCheck = $data['check'];

            $processedText = $this->openAiFunctions->openAIReminderTextProcessor($reminderText, $reminderEmotion);
            
            $reminders = $this->jsonFunctions->readRemindersFromFile();
            $reminders[] = new Reminder(
                $reminderId,
                $processedText,
                $reminderEmotion,
                new \DateTime($reminderDate['date']),
                $reminderCheck
            );
            $this->jsonFunctions->writeRemindersToFile($reminders);
        };
    
        $channel->basic_consume('reminderQueue', '', false, true, false, false, $callback);
    
        while ($channel->is_consuming()) {
            $channel->wait();
        }
    
        $channel->close();
        $connection->close();
    }
}