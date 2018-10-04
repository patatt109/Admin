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

use Phact\Controller\Controller;
use Phact\Interfaces\AuthInterface;
use Phact\Request\HttpRequestInterface;

class BackendController extends Controller
{
    /**
     * @var AuthInterface
     */
    protected $_auth;

    public function __construct(HttpRequestInterface $request, AuthInterface $auth)
    {
        $this->_auth = $auth;

        parent::__construct($request);
    }

    public function beforeAction($action, $params)
    {
        $user = $this->_auth->getUser();
        if (!$user || $user->getIsGuest()) {
            $this->request->redirect('admin:login');
        } elseif (!$user->getIsSuperuser()) {
            $this->error(404);
        }
    }
}