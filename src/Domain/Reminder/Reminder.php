<?php

declare(strict_types=1);

namespace App\Domain\Reminder;

use DateTime;
use JsonSerializable;

class Reminder implements JsonSerializable
{
    private ?string $id;

    private string $text;

    private string $emotion;
    
    private DateTime $date;

    private ?bool $check;

    public function __construct(string $id, string $text, string $emotion, DateTime $date, bool $check)
    {
        if (empty($text)) throw new \InvalidArgumentException("O texto não pode estar vazio.");
        if (empty($emotion)) throw new \InvalidArgumentException("A emoção não pode estar vazia.");
        if (empty($date)) throw new \InvalidArgumentException("A data não pode estar vazia.");

        $this->id = $id;
        $this->text = ucfirst($text);
        $this->emotion = $emotion;
        $this->date = $date;
        $this->check = $check;
    }

    public function getId(): ?string
    {
        return $this->id;
    }

    public function setId(?string $id): Reminder
    {
        $this->id = $id;
        return $this;
    }

    public function getText(): string
    {
        return $this->text;
    }

    public function setText(string $text): Reminder
    {
        $this->text = ucfirst($text);
        return $this;
    }

    public function getEmotion(): string
    {
        return $this->emotion;
    }

    public function setEmotion(string $emotion): Reminder
    {
        $this->emotion = $emotion;
        return $this;
    }

    public function getDate(): Datetime
    {
        return $this->date;
    }

    public function setDate(Datetime $date): void
    {
        $this->date = $date;
    }

    public function getCheck(): bool
    {
        return $this->check;
    }

    public function setCheck(bool $check): void
    {
        $this->check = $check;
    }

    #[\ReturnTypeWillChange]
    public function jsonSerialize(): array
    {
        return [
            'id' => $this->id,
            'text' => $this->text,
            'emotion' => $this->emotion,
            'date' => $this->date->format('Y-m-d H:i:s'),
            'check' => $this->check
        ];
    }
}
