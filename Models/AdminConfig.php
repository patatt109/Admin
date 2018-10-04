<?php
/**
 *
 *
 * All rights reserved.
 *
 * @author Okulov Anton
 * @email qantus@mail.ru
 * @version 1.0
 * @date 02/11/16 16:49
 */

namespace Modules\Admin\Models;

use Phact\Orm\Fields\CharField;
use Phact\Orm\Fields\TextField;
use Phact\Orm\Model;

class AdminConfig extends Model
{
    public static function getFields()
    {
        return [
            'module' => [
                'class' => CharField::class,
                'label' => 'Module'
            ],
            'admin' => [
                'class' => CharField::class,
                'label' => 'Admin'
            ],
            'user_login' => [
                'class' => CharField::class,
                'label' => 'User id'
            ],
            // Comma-separated columns
            'columns' => [
                'class' => TextField::class,
                'label' => 'Columns',
                'null' => true
            ]
        ];
    }

    public function getColumnsList()
    {
        $columns = $this->columns;
        return explode(',', $columns);
    }

    public function setColumnsList($columns)
    {
        $this->columns = implode(',', $columns);
        $this->save();
    }

    public static function fetch($module, $admin, $userLogin)
    {
        $model = self::objects()->filter([
            'module' => $module,
            'admin' => $admin,
            'user_login' => $userLogin
        ])->get();
        if (!$model) {
            $model = new self();
            $model->module = $module;
            $model->admin = $admin;
            $model->user_login = $userLogin;
        }
        return $model;
    }
}