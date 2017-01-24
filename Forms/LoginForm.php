<?php
/**
 *
 *
 * All rights reserved.
 *
 * @author Okulov Anton
 * @email qantus@mail.ru
 * @version 1.0
 * @company HashStudio
 * @site http://hashstudio.ru
 * @date 07/08/16 16:17
 */

namespace Modules\Admin\Forms;

use Modules\User\Models\User;
use Modules\User\UserModule;
use Phact\Form\Fields\EmailField;
use Phact\Form\Fields\PasswordField;
use Phact\Form\Form;
use Phact\Main\Phact;

class LoginForm extends Form
{
    public function getFields()
    {
        return [
            'email' => [
                'class' => EmailField::class,
                'label' => 'E-mail',
                'required' => true
            ],
            'password' => [
                'class' => PasswordField::class,
                'label' => 'Пароль',
                'required' => true
            ]
        ];
    }

    public function clean($attributes)
    {
        $email = $attributes['email'];
        $password = $attributes['password'];

        $hasher = UserModule::getPasswordHasher();
        
        $user = $this->getUser($email);
        if ($user) {
            if (!$hasher::verify($password, $user->password)) {
                $this->addError('password', 'Некорректный пароль');
            }
        } else {
            $this->addError('email', 'Пользователь с таким e-mail адресом не зарегистрирован');
        }
    }

    public function login()
    {
        $data = $this->getAttributes();
        $user = $this->getUser($data['email']);
        if ($user) {
            Phact::app()->auth->login($user);
        }
    }

    public function getUser($email)
    {
        return User::objects()->filter(['email' => $email])->get();
    }
}