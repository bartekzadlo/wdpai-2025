<?php

class UserFriend
{
    public string $id;
    public string $userId;
    public string $friendId;
    public string $status; // 'pending', 'accepted', 'blocked'
    public string $createdAt;

    public function __construct(
        string $id,
        string $userId,
        string $friendId,
        string $status = 'pending',
        string $createdAt = null
    ) {
        $this->id = $id;
        $this->userId = $userId;
        $this->friendId = $friendId;
        $this->status = $status;
        $this->createdAt = $createdAt ?? date('Y-m-d H:i:s');
    }

    public static function fromArray(array $data): self
    {
        return new self(
            $data['id'] ?? '',
            $data['userId'] ?? '',
            $data['friendId'] ?? '',
            $data['status'] ?? 'pending',
            $data['createdAt'] ?? date('Y-m-d H:i:s')
        );
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'userId' => $this->userId,
            'friendId' => $this->friendId,
            'status' => $this->status,
            'createdAt' => $this->createdAt,
        ];
    }
}
