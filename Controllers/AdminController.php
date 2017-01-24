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

use Modules\Admin\Contrib\Admin;
use Phact\Main\Phact;

class AdminController extends BackendController
{
    public function all($module, $admin, $parentId = null)
    {
        $admin = $this->getAdmin($module, $admin, $parentId);
        $this->setBreadcrumbs($admin);
        $admin->all();
    }

    public function create($module, $admin, $parentId = null)
    {
        $admin = $this->getAdmin($module, $admin, $parentId);
        $this->setBreadcrumbs($admin, 'Создание');
        $admin->create($parentId);
    }

    public function update($module, $admin, $pk)
    {
        $admin = $this->getAdmin($module, $admin);
        $this->setBreadcrumbs($admin, 'Редактирование');
        $admin->update($pk);
    }

    public function remove($module, $admin, $pk)
    {
        if (!$this->request->getIsPost()) {
            $this->error(404);
        }
        $admin = $this->getAdmin($module, $admin);
        $admin->remove($pk);
    }

    public function sort($module, $admin, $parentId = null)
    {
        $admin = $this->getAdmin($module, $admin, $parentId);

        $pkList = isset($_POST['pk_list']) && is_array($_POST['pk_list']) ? $_POST['pk_list'] : [];
        $to = isset($_POST['to']) ? $_POST['to'] : null;
        $prev = isset($_POST['prev']) ? $_POST['prev'] : null;
        $next = isset($_POST['next']) ? $_POST['next'] : null;

        $admin->sort($pkList, $to , $prev, $next);
    }

    public function columns($module, $admin)
    {
        $admin = $this->getAdmin($module, $admin);

        $columns = isset($_POST['columns']) && is_array($_POST['columns']) ? $_POST['columns'] : [];

        $admin->setColumns($columns);
    }

    public function groupAction($module, $admin)
    {
        if (!$this->request->getIsPost()) {
            $this->error(404);
        }
        $admin = $this->getAdmin($module, $admin);
        $action = isset($_POST['action']) ? $_POST['action'] : null;
        $pkList = isset($_POST['pk_list']) && is_array($_POST['pk_list']) ? $_POST['pk_list'] : [];

        if ($action) {
            $admin->handleGroupAction($action, $pkList);
        } else {
            $this->error(404);
        }
    }

    /**
     * @param $admin Admin
     */
    public function setBreadcrumbs($admin, $last = null)
    {
        $breadcrumbs = $admin->getBreadcrumbs();
        foreach ($breadcrumbs as $breadcrumb) {
            Phact::app()->breadcrumbs->add(
                $breadcrumb['name'],
                isset($breadcrumb['url']) ? $breadcrumb['url']: null
            );
        }

        if ($last) {
            Phact::app()->breadcrumbs->add($last);
        }
    }

    /**
     * @param $module
     * @param $admin
     * @param $parentId
     * @return Admin
     * @throws \Phact\Exceptions\HttpException
     */
    public function getAdmin($module, $admin, $parentId = null)
    {
        $class = "Modules\\{$module}\\Admin\\{$admin}";
        if (class_exists($class)) {
            $admin = new $class;
            if ($parentId) {
                $admin->parentId = $parentId;
            }
            return $admin;
        }
        $this->error(404);
    }
}