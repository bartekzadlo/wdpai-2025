<?php

// Główna klasa kontrolera aplikacji - zawiera wspólną funkcjonalność dla wszystkich kontrolerów
class AppController
{

  // Metoda renderująca widok - pobiera szablon i zmienne, generuje wyjście HTML
  protected function render(string $template = null, array $variables = [])
  {
    // Ścieżka do szablonu widoku
    $templatePath = 'public/views/' . $template . '.html';
    // Ścieżka do strony błędu 404
    $templatePath404 = 'public/views/404.html';
    $output = "";

    // Sprawdzenie czy szablon istnieje
    if (file_exists($templatePath)) {
      // Przykładowe zmienne: ["messages" => "Błędne hasło!"]
      extract($variables);
      // Po ekstrakcji: $messages = "Błędne hasło!"
      // Można używać w szablonie: echo $messages

      // Rozpoczęcie buforowania wyjścia
      ob_start();
      include $templatePath;
      $output = ob_get_clean();
    } else {
      // Jeśli szablon nie istnieje, użyj strony 404
      ob_start();
      include $templatePath404;
      $output = ob_get_clean();
    }
    // Wyświetlenie wygenerowanego wyjścia
    echo $output;
  }
}
