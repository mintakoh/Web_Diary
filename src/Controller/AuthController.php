<?php

namespace Controller;


use Model\User;

class AuthController
{
    public static function index()
    {
        $error = isset($_GET['error']);
        view()->render('auth', ['error' => $error]);
    }

    public static function login()
    {
        /** @var \Repository\UserRepositoryInterface $userRepository */
        $userRepository = \IoC::resolve('userStore');

        /** @var User $user */
        $user = $userRepository->getUserById($_POST['id']);

        if($user !== null && $user->getPassword() == $_POST['password']) {
            $_SESSION['user_id'] = $user->getId();
            header('Location: /');
        } else {
            $_SESSION['user_id'] = null;
            header('Location: /?r=/auth&error=NOT_VALID_USER');
        }
    }

    public static function logout()
    {
        $_SESSION['user_id'] = null;
        header('Location: /');
    }

    public static function join()
    {
        view()->render('join');
    }

    public static function joinRequest()
    {
        $id = $_POST['id'];
        $password = $_POST['password'];
        $name = $_POST['name'];

        /** @var \Repository\UserRepositoryInterface $userRepository */
        $userRepository = \IoC::resolve('userStore');
        $user = $userRepository->getUserById($id);

        if($user !== null) {
            if (isset($_SERVER["HTTP_REFERER"])) {
                header("Location: " . $_SERVER["HTTP_REFERER"]);
            }
            else {
                header('Location: /');
            }
        }

        $newUser = new User($id, $password, $name);
        $userRepository->createUser($newUser);

        $_SESSION['user_id'] = $newUser->getId();
        header('Location: /');
    }

}