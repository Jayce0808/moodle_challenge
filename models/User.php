<?php 

class User {
    private $name; 
    private $surname;
    private $email;

    public function __construct($name, $surname, $email) {
        $this->setName($this->formatName($name));
        $this->setSurname($this->formatName($surname));
        $this->setEmail($this->formatEmail($email));
        $this->validateEmail();
    }

    public function getName() {
        return $this->name;
    }

    public function setName($name) {//TODO: format name
        $this->name = $name;
    }

    public function getSurname() {
        return $this->surname;
    }

    public function setSurname($surname) {//TODO: format surname
        $this->surname = $surname;
    }

    public function getEmail() {
        return $this->email;
    }

    public function setEmail($email) {
        $this->email = $email;
    }

    private function validateEmail() {
        return true;
    }

    private function formatName($name) {
        return $name;
    }

    private function formatEmail($email) {
        return $email;
    }
}