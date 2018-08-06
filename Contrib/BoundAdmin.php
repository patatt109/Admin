<?php
/**
 *
 *
 * All rights reserved.
 *
 * @author Okulov Anton
 * @email qantus@mail.ru
 * @version 1.0
 * @date 08/08/16 08:22
 */

namespace Modules\Admin\Contrib;


abstract class BoundAdmin extends Admin
{
    public function fetchModel($ownerInstance)
    {
        $model = $this->getModel();
        if ($ownerInstance->pk) {
            $instance = $model::objects()->filter([$this::$ownerAttribute => $ownerInstance->pk])->get();
        } else {
            $instance = $this->newModel();
        }
        return $instance;
    }

    public function save($form, $ownerInstance)
    {
        $form->save([$this::$ownerAttribute => $ownerInstance->pk]);
    }
}