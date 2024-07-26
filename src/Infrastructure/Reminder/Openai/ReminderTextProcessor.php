<?php

declare(strict_types=1);

namespace App\Infrastructure\Reminder\Openai;

class ReminderTextProcessor
{
    private $openAiKey;

    public function __construct()
    {
        $this->openAiKey = getenv('OPEN_AI_KEY') ?: $_ENV['OPEN_AI_KEY'];
    }

    /**
     * OpenAI text reminder processor based on the reminder emotion.
     *
     * @param string $text
     * @param string $emotion
     */
    public function openAIReminderTextProcessor(string $reminderText, string $reminderEmotion): string 
    {
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
            "Authorization: Bearer $this->openAiKey"
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