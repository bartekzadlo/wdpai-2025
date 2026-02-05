<?php

require_once __DIR__ . '/../models/EventStatus.php';

class ValidationHelper
{
    public static function validateUserData(array $data, bool $isUpdate = false): array
    {
        $errors = [];

        $email = trim($data['email'] ?? '');
        $name = trim($data['name'] ?? '');
        $surname = trim($data['surname'] ?? '');
        $phone = trim($data['phone'] ?? '');
        $city = trim($data['city'] ?? '');

        if (empty($email) || empty($name) || empty($surname) || empty($city)) {
            $errors[] = 'Wypełnij wszystkie pola';
        }

        if (strlen($email) > 255) {
            $errors[] = 'Email too long';
        }
        if (strlen($name) > 50) {
            $errors[] = 'Name too long';
        }
        if (strlen($surname) > 50) {
            $errors[] = 'Surname too long';
        }
        if (strlen($phone) > 20) {
            $errors[] = 'Phone too long';
        }
        if (strlen($city) > 100) {
            $errors[] = 'City too long';
        }

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errors[] = 'Niepoprawny email';
        }

        return $errors;
    }

    public static function validatePassword(string $password): array
    {
        $errors = [];

        if (strlen($password) < 8) {
            $errors[] = 'Hasło zbyt krótkie';
        }
        if (strlen($password) > 128) {
            $errors[] = 'Hasło zbyt długie';
        }
        if (!preg_match('/[A-Z]/', $password)) {
            $errors[] = 'Hasło musi zawierać przynajmniej jedną wielką literę';
        }
        if (!preg_match('/[!@#$%^&*()_+\-=\[\]{};\':"\\|,.<>\/?]/', $password)) {
            $errors[] = 'Hasło musi zawierać przynajmniej jeden znak specjalny';
        }

        return $errors;
    }

    public static function validateEventData(array $data): array
    {
        $errors = [];

        $title = trim($data['title'] ?? '');
        $location = trim($data['location'] ?? '');
        $date = trim($data['date'] ?? '');
        $imageUrl = trim($data['imageUrl'] ?? '');

        if (empty($title)) {
            $errors[] = 'Nazwa wydarzenia jest wymagana';
        }
        if (empty($location)) {
            $errors[] = 'Lokalizacja jest wymagana';
        }
        if (empty($date)) {
            $errors[] = 'Data jest wymagana';
        } elseif (!preg_match('/^\d{2}\.\d{2}\.\d{4}$/', $date)) {
            $errors[] = 'Data musi być w formacie DD.MM.YYYY';
        } else {
            $dateObj = DateTime::createFromFormat('d.m.Y', $date);
            if (!$dateObj) {
                $errors[] = 'Nieprawidłowy format daty';
            } elseif ($dateObj < new DateTime()) {
                $errors[] = 'Data musi być w przyszłości';
            }
        }
        if (empty($imageUrl)) {
            $errors[] = 'URL obrazka jest wymagany';
        } elseif (!filter_var($imageUrl, FILTER_VALIDATE_URL)) {
            $errors[] = 'Nieprawidłowy URL obrazka';
        }

        return $errors;
    }

    public static function setEventStatus(string $date, string $currentStatus): string
    {
        if ($currentStatus === EventStatus::PENDING) {
            return $currentStatus;
        }
        $currentDate = date('d.m.Y');
        return (strtotime($date) >= strtotime($currentDate)) ? EventStatus::ACTIVE : EventStatus::INACTIVE;
    }
}
