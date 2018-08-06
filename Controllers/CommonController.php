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

class CommonController extends BackendController
{
    public function index()
    {
        echo $this->render('admin/index.tpl', [

        ]);
    }
}