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
    public array $consents;
    public array $settings;

    public function __construct(
        string $id,
        string $email,
        string $password,
        string $role,
        string $name,
        string $surname,
        string $phone,
        string $city,
        array $consents = [],
        array $settings = []
    ) {
        $this->id = $id;
        $this->email = $email;
        $this->password = $password;
        $this->role = $role;
        $this->name = $name;
        $this->surname = $surname;
        $this->phone = $phone;
        $this->city = $city;
        $this->consents = $consents;
        $this->settings = $settings;
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
            $data['consents'] ?? [],
            $data['settings'] ?? []
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
            'consents' => $this->consents,
            'settings' => $this->settings,
        ];
    }
}
