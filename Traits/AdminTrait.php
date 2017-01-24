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
 * @date 03/10/16 10:04
 */

namespace Modules\Admin\Traits;


use Modules\Admin\Contrib\Admin;
use Phact\Main\Phact;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;

/**
 * Class AdminTrait
 * @package Modules\Admin\Traits
 */
trait AdminTrait
{
    public static $adminFolder = 'Admin';

    public static function getAdminMenu()
    {
        $menu = [];
        $adminClasses = static::getAdminClasses();
        foreach ($adminClasses as $adminClass) {
            if (is_a($adminClass, Admin::class, true) && $adminClass::$public) {
                $menu[] = [
                    'adminClassName' => $adminClass::className(),
                    'adminClassNameShort' => $adminClass::classNameShort(),
                    'moduleName' => static::getName(),
                    'name' => $adminClass::getName(),
                    'route' => Phact::app()->router->url('admin:all', [
                        'module' => static::getName(),
                        'admin' => $adminClass::classNameShort()
                    ])
                ];
            }
        }
        return $menu;
    }

    public static function getAdminClasses()
    {
        $classes = [];
        $modulePath = self::getPath();
        $path = implode(DIRECTORY_SEPARATOR, [$modulePath, static::$adminFolder]);
        if (is_dir($path)) {
            foreach (new RecursiveIteratorIterator(new RecursiveDirectoryIterator($path)) as $filename)
            {
                if ($filename->isDir()) continue;
                $name = $filename->getBasename('.php');
                $classes[] = implode('\\', ['Modules', static::getName(), static::$adminFolder, $name]);
            }
        }
        return $classes;
    }
}