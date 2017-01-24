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

use Modules\User\Forms\LoginForm;
use Modules\User\Helpers\Password;
use Modules\User\Models\User;
use Phact\Controller\Controller;
use Phact\Main\Phact;

class BackendController extends Controller
{
    public function beforeAction($action, $params)
    {
        $user = Phact::app()->auth->getUser();
        if (!$user || $user->is_guest) {
            $this->request->redirect('admin:login');
        } elseif (!$user->is_superuser) {
            $this->error(404);
        }
    }
}