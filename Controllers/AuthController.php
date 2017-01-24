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
 * @date 19/05/16 07:48
 */

namespace Modules\Admin\Controllers;

use Modules\Admin\Forms\LoginForm;
use Modules\User\Models\User;
use Phact\Controller\Controller;
use Phact\Main\Phact;

class AuthController extends Controller
{
    public function login()
    {
        /** @var User $user */
        $user = Phact::app()->getUser();
        if (!$user->getIsGuest()) {
            $this->redirect('admin:index');
        }
        $form = new LoginForm();
        if ($this->request->getIsPost() && $form->fill($_POST)) {
            if ($form->valid) {
                $form->login();
                $this->redirect('admin:index');
            }
        }
        echo $this->render('admin/auth/login.tpl', [
            'form' => $form
        ]);
    }

    public function logout()
    {
        Phact::app()->auth->logout();
        $this->redirect('admin:login');
    }
}