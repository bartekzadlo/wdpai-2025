<?php

require_once 'AppController.php';

class SecurityController extends AppController
{

  public function login()
  {
    return $this->render('login', ["message" => "Błędne hasło lub login"]);
  }

  public function register()
  {
    return $this->render('register');
  }
}