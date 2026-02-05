<?php

class User
{
    public string $id;
    public string $email;
    public string $password;
    public string $role;
    public string $name;
    public string $surname;
    public string $phone;
    public string $city;
    public string $profilePicture;
    public array $consents;

    public function __construct(
        string $id,
        string $email,
        string $password,
        string $role,
        string $name,
        string $surname,
        string $phone,
        string $city,
        string $profilePicture = '',
        array $consents = [],
        ?string $bio = null
    ) {
        $this->id = $id;
        $this->email = $email;
        $this->password = $password;
        $this->role = $role;
        $this->name = $name;
        $this->surname = $surname;
        $this->phone = $phone;
        $this->city = $city;
        $this->profilePicture = $profilePicture;
        $this->consents = $consents;
        $this->bio = $bio;
    }

    public static function fromArray(array $data): self
    {
        return new self(
            $data['id'] ?? '',
            $data['email'] ?? '',
            $data['password'] ?? '',
            $data['role'] ?? 'user',
            $data['name'] ?? '',
            $data['surname'] ?? '',
            $data['phone'] ?? '',
            $data['city'] ?? '',
            $data['profilePicture'] ?? '',
            $data['consents'] ?? [],
            $data['bio'] ?? null
        );
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'email' => $this->email,
            'password' => $this->password,
            'role' => $this->role,
            'name' => $this->name,
            'surname' => $this->surname,
            'phone' => $this->phone,
            'city' => $this->city,
            'profilePicture' => $this->profilePicture,
            'consents' => $this->consents,
            'bio' => $this->bio,
        ];
    }
}
