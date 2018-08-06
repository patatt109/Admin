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


use Phact\Form\ModelForm;
use Phact\Main\Phact;
use Phact\Module\Module;
use Phact\Orm\Model;

class SettingsController extends BackendController
{
    public function index($module)
    {
        /** @var Module $module */
        $module = Phact::app()->getModule($module);
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

        Phact::app()->breadcrumbs->add('Настройки модуля "' . $module::getVerboseName() . '"');

        if ($this->request->getIsPost() && $settingsForm->fill($_POST, $_FILES) && $settingsForm->valid) {
            $settingsForm->save();
            $module->afterSettingsUpdate();
            Phact::app()->flash->success('Изменения сохранены');
            $this->request->refresh();
        }

        echo $this->render('admin/settings.tpl', [
            'form' => $settingsForm,
            'model' => $model,
            'settingsModule' => $module
        ]);
    }
}