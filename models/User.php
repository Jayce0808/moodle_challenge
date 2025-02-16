<?php 

/**
 * Class User
 * 
 * Represents a user with basic details such as name, surname, and email.
 * Provides methods to retrieve and update user information while ensuring 
 * proper formatting and validation.
 */
class User {
    /**
     * @var string $name The user's first name.
     */
    private $name; 
    /**
     * @var string $surname The user's last name.
     */
    private $surname;
    /**
     * @var string $email The user's email address.
     */
    private $email;

    /**
     * User constructor.
     * 
     * Initializes a new User object and ensures proper formatting and validation.
     *
     * @param string $name    The user's first name.
     * @param string $surname The user's last name.
     * @param string $email   The user's email address.
     * 
     * @throws InvalidUserException If there is an error with the data provided for the user.
     */
    public function __construct($name, $surname, $email) {
        $this->setName($name);
        $this->setSurname($surname);
        $this->setEmail($email);
        $this->validateEmail();
    }

    public function getName(): string {
        return $this->name;
    }

    public function setName($name): void {
        $this->name = $this->formatName($name);
    }

    public function getSurname(): string {
        return $this->surname;
    }

    public function setSurname($surname): void {
        $this->surname = $this->formatName($surname);
    }

    public function getEmail(): string {
        return $this->email;
    }

    public function setEmail($email): void {
        $this->email = $this->formatEmail($email);
    }

    /**
     * Will check if the email of this user is valid
     * throws InvalidUserException if email is not valid 
     * and true if email is valid
     * 
     * @throws InvalidUserException if the email is invalid
     * @return true if email is valid 
     */
    private function validateEmail(): bool {
        if (!filter_var($this->getEmail(), FILTER_VALIDATE_EMAIL)) {
            throw new InvalidUserException("Error: The provided email " . $this->getEmail() . " is not valid.\n");
        } 
        return true;
    }
    /**
     * Formats a name by converting it to lowercase and capitalizing the first letter.
     *
     * @param string $name The name to format.
     * @return string The formatted name.
     */
    private function formatName($name): string {
        return ucfirst(strtolower($name));
    }
    /**
     * Formats an email address by converting it to lowercase.
     *
     * @param string $email The email to format.
     * @return string The formatted email.
     */
    private function formatEmail($email): string {
        return strtolower($email);
    }
}