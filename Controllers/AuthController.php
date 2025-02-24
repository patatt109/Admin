<?php
/**
 *
 *
 * All rights reserved.
 *
 * @author Okulov Anton
 * @email qantus@mail.ru
 * @version 1.0
 * @date 19/05/16 07:48
 */

namespace Modules\Admin\Controllers;

use Modules\Admin\Forms\LoginForm;
use Modules\User\Models\User;
use Phact\Controller\Controller;
use Phact\Di\ContainerInterface;
use Phact\Interfaces\AuthInterface;
use Phact\Main\Phact;
use Phact\Request\HttpRequestInterface;
use Phact\Template\RendererInterface;
use Phact\Template\TemplateManager;

class AuthController extends Controller
{
    /**
     * @var AuthInterface
     */
    protected $_auth;

    public function __construct(HttpRequestInterface $request, AuthInterface $auth, RendererInterface $renderer)
    {
        $this->_auth = $auth;

        parent::__construct($request, $renderer);
    }

    public function login()
    {
        $user = $this->_auth->getUser();
        if (!$user->getIsGuest()) {
            $this->redirect('admin:index');
        }
        $form = new LoginForm($this->_auth, []);
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
        $this->_auth->logout();
        $this->redirect('admin:login');
    }
}