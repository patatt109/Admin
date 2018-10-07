<?php
/**
 *
 *
 * All rights reserved.
 *
 * @author Okulov Anton
 * @email qantus@mail.ru
 * @version 1.0
 * @date 30/01/17 15:14
 */

namespace Modules\Admin\Controllers;

use Phact\Application\ModulesInterface;
use Phact\Components\BreadcrumbsInterface;
use Phact\Components\FlashInterface;
use Phact\Form\ModelForm;
use Phact\Interfaces\AuthInterface;
use Phact\Main\Phact;
use Phact\Module\Module;
use Phact\Orm\Model;
use Phact\Request\HttpRequestInterface;
use Phact\Template\RendererInterface;
use Phact\Translate\Translate;

class SettingsController extends BackendController
{
    /** @var BreadcrumbsInterface */
    protected $_breadcrumbs;

    /** @var ModulesInterface */
    protected $_modules;

    /** @var FlashInterface */
    protected $_flash;

    /** @var Translate */
    protected $_translate;

    public function __construct(
        HttpRequestInterface $request,
        AuthInterface $auth,
        ModulesInterface $modules,
        RendererInterface $renderer,
        FlashInterface $flash = null,
        BreadcrumbsInterface $breadcrumbs = null,
        Translate $translate = null
    )
    {
        $this->_modules = $modules;
        $this->_breadcrumbs = $breadcrumbs;
        $this->_flash = $flash;
        $this->_translate = $translate;

        parent::__construct($request, $auth, $renderer);
    }

    public function index($module)
    {
        /** @var Module $module */
        $module = $this->_modules->getModule($module);
        /** @var Model $settingsModel */
        $settingsModel = $module->getSettingsModel();
        if (!$settingsModel) {
            $this->error(404);
        }
        $model = $settingsModel->objects()->get();
        if (!$model) {
            $model = $settingsModel;
        }
        /** @var ModelForm $settingsForm */
        $settingsForm = $module->getSettingsForm();
        $settingsForm->setModel($model);
        $settingsForm->setInstance($model);

        if ($this->_breadcrumbs && $this->_translate) {
            $message = $this->_translate->t('Admin.main', 'Settings of module');
            $this->_breadcrumbs->add($message . ' "' . $module->getVerboseName() . '"');
        }

        if ($this->request->getIsPost() && $settingsForm->fill($_POST, $_FILES) && $settingsForm->valid) {
            $settingsForm->save();
            $module->afterSettingsUpdate();
            if ($this->_flash && $this->_translate) {
                $this->_flash->success($this->_translate->t('Admin.main', 'Changes saved'));
            }
            $this->request->refresh();
        }

        echo $this->render('admin/settings.tpl', [
            'form' => $settingsForm,
            'model' => $model,
            'settingsModule' => $module
        ]);
    }
}