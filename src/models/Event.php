<?php

require_once 'EventStatus.php';

// Klasa reprezentująca wydarzenie w aplikacji
class Event
{
    // Unikalny identyfikator wydarzenia
    public string $id;
    // Tytuł wydarzenia
    public string $title;
    // Lokalizacja wydarzenia
    public string $location;
    // Data wydarzenia
    public string $date;
    // Data utworzenia wydarzenia
    public string $createdAt;
    // URL obrazka wydarzenia
    public string $imageUrl;
    // Opis wydarzenia
    public string $description;
    // Liczba zainteresowań wydarzeniem
    public int $interestCount;
    // Czy użytkownik jest zainteresowany wydarzeniem
    public bool $isInterested;
    // Status wydarzenia (aktywne, nieaktywne, oczekujące)
    public string $status;
    // Maksymalna liczba uczestników
    public ?int $maxParticipants;

    public function __construct(
        string $id,
        string $title,
        string $location,
        string $date,
        string $createdAt = null,
        string $imageUrl = '',
        string $description = '',
        string $status = '',
        ?int $maxParticipants = null,
        int $interestCount = 0,
        bool $isInterested = false
    ) {
        $this->id = $id;
        $this->title = $title;
        $this->location = $location;
        $this->date = $date;
        $this->createdAt = $createdAt ?? date('Y-m-d H:i:s');
        $this->imageUrl = $imageUrl;
        $this->description = $description;
        $this->status = $status;
        $this->maxParticipants = $maxParticipants;
        $this->interestCount = $interestCount;
        $this->isInterested = $isInterested;
    }

    public static function fromArray(array $data): self
    {
        return new self(
            $data['id'] ?? '',
            $data['title'] ?? '',
            $data['location'] ?? '',
            $data['date'] ?? '',
            $data['createdAt'] ?? null,
            $data['imageUrl'] ?? '',
            $data['description'] ?? '',
            $data['status'] ?? '',
            $data['max_participants'] ?? $data['maxParticipants'] ?? null,
            $data['interestCount'] ?? 0,
            false
        );
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'title' => $this->title,
            'location' => $this->location,
            'date' => $this->date,
            'createdAt' => $this->createdAt,
            'imageUrl' => $this->imageUrl,
            'description' => $this->description,
            'status' => $this->status,
            'maxParticipants' => $this->maxParticipants,
            'interestCount' => $this->interestCount,
        ];
    }
}
