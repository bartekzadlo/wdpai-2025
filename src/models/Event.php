<?php

class Event
{
    public string $id;
    public string $title;
    public string $location;
    public string $date;
    public string $imageUrl;
    public int $interestCount;
    public bool $isInterested;

    public function __construct(
        string $id,
        string $title,
        string $location,
        string $date,
        string $imageUrl,
        int $interestCount = 0,
        bool $isInterested = false
    ) {
        $this->id = $id;
        $this->title = $title;
        $this->location = $location;
        $this->date = $date;
        $this->imageUrl = $imageUrl;
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
            $data['imageUrl'] ?? '',
            $data['interestCount'] ?? 0
        );
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'title' => $this->title,
            'location' => $this->location,
            'date' => $this->date,
            'imageUrl' => $this->imageUrl,
            'interestCount' => $this->interestCount,
        ];
    }
}
