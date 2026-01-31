<?php

class UserEventInterest
{
    public string $userId;
    public string $eventId;

    public function __construct(string $userId, string $eventId)
    {
        $this->userId = $userId;
        $this->eventId = $eventId;
    }

    public static function fromArray(array $data): self
    {
        return new self(
            $data['userId'] ?? '',
            $data['eventId'] ?? ''
        );
    }

    public function toArray(): array
    {
        return [
            'userId' => $this->userId,
            'eventId' => $this->eventId,
        ];
    }
}
