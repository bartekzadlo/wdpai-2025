<?php

// Klasa definiująca statusy wydarzeń - stałe używane w aplikacji do oznaczania stanu wydarzeń
class EventStatus
{
    // Wydarzenie aktywne - widoczne dla użytkowników
    const ACTIVE = 'AKTYWNE';
    // Wydarzenie nieaktywne - zakończone lub nieaktywne
    const INACTIVE = 'NIEAKTYWNE';
    // Wydarzenie oczekujące - oczekuje na zatwierdzenie przez administratora
    const PENDING = 'OCZEKUJACE';
}
