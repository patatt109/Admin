<?php
/**
 *
 *
 * All rights reserved.
 *
 * @author Okulov Anton
 * @email qantus@mail.ru
 * @version 1.0
 * @date 03/10/16 10:04
 */

namespace Modules\Admin\Traits;


use Modules\Admin\Contrib\Admin;
use Phact\Di\ComponentFetcher;
use Phact\Main\Phact;
use Phact\Router\RouterInterface;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;

/**
 * Class AdminTrait
 * @package Modules\Admin\Traits
 */
trait AdminTrait
{
    use ComponentFetcher;

    public static $adminFolder = 'Admin';

    /**
     * Build admin menu
     * @return array
     * @throws \Exception
     */
    public static function getAdminMenu()
    {
        /** @var RouterInterface $router */
        $router = self::fetchComponent(RouterInterface::class);
        if (!$router) {
            return [];
        }
        $menu = [];
        $adminClasses = static::getAdminClasses();
        foreach ($adminClasses as $adminClass) {
            if (is_a($adminClass, Admin::class, true) && $adminClass::getIsPublic()) {
                $menu[] = [
                    'adminClassName' => $adminClass::className(),
                    'adminClassNameShort' => $adminClass::classNameShort(),
                    'moduleName' => static::getName(),
                    'name' => $adminClass::getName(),
                    'route' => $router->url('admin:all', [
                        'module' => static::getName(),
                        'admin' => $adminClass::classNameShort()
                    ])
                ];
            }
        }
        return $menu;
    }

    /**
     * Get admin classes of current module
     * @return array
     */
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