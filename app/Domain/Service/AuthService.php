<?php

declare(strict_types=1);

namespace App\Domain\Service;

use App\Domain\Entity\User;
use App\Domain\Repository\UserRepositoryInterface;

class AuthService
{
    public function __construct(
        private readonly UserRepositoryInterface $users,
    ) {}

    public function register(string $username, string $password, string $passwordVerification): User
    {
        // TODO: check that a user with same username does not exist, create new user and persist
        // TODO: make sure password is not stored in plain, and proper PHP functions are used for that

        // TODO: here is a sample code to start with

        if ($password !== $passwordVerification) {
            throw new \InvalidArgumentException('Passwords do not match.');
        }

        if($this->users->findByUsername($username) !== null) {
            throw new \InvalidArgumentException('Username already exists.');
        }

        if(empty($username) || empty($password)) {
            throw new \InvalidArgumentException('Username and password cannot be empty.');
        }

        if(strlen($password) < 8) {
            throw new \InvalidArgumentException('Password must be at least 8 characters long.');
        }

        if(!preg_match('/\d/', $password)){
            throw new \InvalidArgumentException('Password must contain at least one digit.');
        }

        $password = password_hash($password, PASSWORD_DEFAULT);
        if ($password === false) {
            throw new \RuntimeException('Failed to hash password.');
        }

        $user = new User(null, $username, $password, new \DateTimeImmutable());
        $this->users->save($user);

        return $user;
    }

    public function attempt(string $username, string $password): bool
    {
        // TODO: implement this for authenticating the user
        // TODO: make sur ethe user exists and the password matches
        // TODO: don't forget to store in session user data needed afterwards
        $user = $this->users->findByUsername($username);
        if ($user === null) {
            return false;
        }

        if (!password_verify($password, $user->getPassword())) {
            return false;
        }

        $_SESSION['user_id'] = $user->id;
        $_SESSION['username'] = $user->username;

        return true;
    }
}
