<?php

require_once 'EventStatus.php';

class Event
{
    public string $id;
    public string $title;
    public string $location;
    public string $date;
    public string $createdAt;
    public string $imageUrl;
    public string $description;
    public int $interestCount;
    public bool $isInterested;
    public string $status;

    public function __construct(
        string $id,
        string $title,
        string $location,
        string $date,
        string $imageUrl,
        string $description = '',
        int $interestCount = 0,
        bool $isInterested = false,
        string $createdAt = null,
        string $status = ''
    ) {
        $this->id = $id;
        $this->title = $title;
        $this->location = $location;
        $this->date = $date;
        $this->createdAt = $createdAt ?? date('Y-m-d H:i:s');
        $this->imageUrl = $imageUrl;
        $this->description = $description;
        $this->interestCount = $interestCount;
        $this->isInterested = $isInterested;
        $this->status = $status;
    }

    public static function fromArray(array $data): self
    {
        return new self(
            $data['id'] ?? '',
            $data['title'] ?? '',
            $data['location'] ?? '',
            $data['date'] ?? '',
            $data['imageUrl'] ?? '',
            $data['description'] ?? '',
            $data['interestCount'] ?? 0,
            false,
            $data['createdAt'] ?? null,
            $data['status'] ?? ''
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
            'interestCount' => $this->interestCount,
        ];
    }
}
