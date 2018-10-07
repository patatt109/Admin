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

use Modules\Admin\Contrib\Admin;
use Modules\Admin\Contrib\AdminMenuInterface;
use Phact\Application\ModulesInterface;
use Phact\Components\BreadcrumbsInterface;
use Phact\Di\ContainerInterface;
use Phact\Interfaces\AuthInterface;
use Phact\Request\HttpRequestInterface;
use Phact\Template\RendererInterface;
use Phact\Translate\Translate;

class AdminController extends BackendController
{
    /**
     * @var ModulesInterface
     */
    protected $_modules;

    /**
     * @var BreadcrumbsInterface
     */
    protected $_breadcrumbs;

    /**
     * @var ContainerInterface
     */
    protected $_container;

    /**
     * @var Translate
     */
    protected $_translate;

    public function __construct(
        ModulesInterface $modules,
        HttpRequestInterface $request,
        BreadcrumbsInterface $breadcrumbs,
        ContainerInterface $container,
        AuthInterface $auth,
        RendererInterface $renderer,
        Translate $translate = null
    )
    {
        $this->_breadcrumbs = $breadcrumbs;
        $this->_container = $container;
        $this->_modules = $modules;
        $this->_translate = $translate;

        parent::__construct($request, $auth, $renderer);
    }

    public function all($module, $admin, $parentId = null)
    {
        $admin = $this->getAdmin($module, $admin, $parentId);
        $this->setBreadcrumbs($admin);
        $admin->all();
    }

    public function create($module, $admin, $parentId = null)
    {
        $admin = $this->getAdmin($module, $admin, $parentId);
        $this->setBreadcrumbs($admin, $this->t('Admin.main', 'Creating'));
        $admin->create($parentId);
    }

    public function update($module, $admin, $pk)
    {
        $admin = $this->getAdmin($module, $admin);
        $this->setBreadcrumbs($admin, $this->t('Admin.main', 'Updating'));
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
            $this->_breadcrumbs->add(
                $breadcrumb['name'],
                isset($breadcrumb['url']) ? $breadcrumb['url']: null
            );
        }

        if ($last) {
            $this->_breadcrumbs->add($last);
        }
    }

    /**
     * @param $module
     * @param $admin
     * @param $parentId
     * @return Admin
     * @throws \Phact\Exceptions\ContainerException
     * @throws \Phact\Exceptions\HttpException
     * @throws \Phact\Exceptions\NotFoundContainerException
     * @throws \ReflectionException
     */
    public function getAdmin($module, $admin, $parentId = null)
    {
        $adminInstance = null;
        $module = $this->_modules->getModule($module);
        if ($module instanceof AdminMenuInterface) {
            $admins = $module->getAdmins();
            if (isset($admins[$admin])) {
                $adminInstance = $admins[$admin];
            }
        }
        if ($adminInstance) {
            if ($parentId) {
                $adminInstance->parentId = $parentId;
            }
            if (isset($_GET['ownerPk'])) {
                $adminInstance->ownerPk = $_GET['ownerPk'];
            }
            $adminInstance->afterInit();
            return $adminInstance;
        }
        $this->error(404);
    }


    /**
     * Translate
     *
     * @param $domain
     * @param string $key
     * @param null $number
     * @param array $parameters
     * @param null $locale
     * @return string
     */
    public function t($domain, $key = "", $number = null, $parameters = [], $locale = null)
    {
        if ($this->_translate) {
            return $this->_translate->t($domain, $key, $number, $parameters, $locale);
        }
        return $key;
    }
}