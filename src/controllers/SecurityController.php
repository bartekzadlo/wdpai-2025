<?php

require_once 'AppController.php';

class SecurityController extends AppController
{

  public function login()
  {
        // TODO get data from database
        // TODO zwroc HTML logowania, przetworz dane
        //  $this->render("login", ["name"=> "Bartek"]);
    return $this->render('login', ["messages" => "Błędne hasło lub login"]);
  }

  public function register()
  {
        // TODO pobranie z formularza email i hasła
        // TODO insert do bazy danych
        // TODO zwrocenie informajci o pomyslnym zarejstrowaniu
            return $this->render("login", ["messages" => "Zarejestrowano uytkownika"]);
  }
}