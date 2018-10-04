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
use Phact\Module\Module;
use Phact\Orm\Model;
use Phact\Request\HttpRequestInterface;
use Phact\Translate\Translator;

class SettingsController extends BackendController
{
    /** @var BreadcrumbsInterface */
    protected $_breadcrumbs;

    /** @var ModulesInterface */
    protected $_modules;

    /** @var FlashInterface */
    protected $_flash;

    /** @var Translator */
    protected $_translator;

    public function __construct(
        HttpRequestInterface $request,
        AuthInterface $auth,
        ModulesInterface $modules,
        FlashInterface $flash = null,
        BreadcrumbsInterface $breadcrumbs = null,
        Translator $translator = null
    )
    {
        $this->_modules = $modules;
        $this->_breadcrumbs = $breadcrumbs;
        $this->_flash = $flash;
        $this->_translator = $translator;

        parent::__construct($request, $auth);
    }

    public function index($module)
    {
        /** @var Module $module */
        $module = $this->_modules->getModule($module);
        /** @var Model $settingsModel */
        $settingsModel = $module::getSettingsModel();
        if (!$settingsModel) {
            $this->error(404);
        }
        $model = $settingsModel->objects()->get();
        if (!$model) {
            $model = $settingsModel;
        }
        /** @var ModelForm $settingsForm */
        $settingsForm = $module::getSettingsForm();
        $settingsForm->setModel($model);
        $settingsForm->setInstance($model);

        if ($this->_breadcrumbs && $this->_translator) {
            $message = $this->_translator->t('Admin.main', 'Settings of module');
            $this->_breadcrumbs->add($message . ' "' . $module::getVerboseName() . '"');
        }

        if ($this->request->getIsPost() && $settingsForm->fill($_POST, $_FILES) && $settingsForm->valid) {
            $settingsForm->save();
            $module->afterSettingsUpdate();
            if ($this->_flash && $this->_translator) {
                $this->_flash->success($this->_translator->t('Admin.main', 'Changes saved'));
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