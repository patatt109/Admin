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
    public $adminFolder = 'Admin';

    /**
     * Build admin menu
     * @return array
     * @throws \Exception
     */
    public function getPublicAdmins(): array
    {
        $items = [];
        $adminInstances = $this->getAdmins();
        foreach ($adminInstances as $name => $admin) {
            if ($admin->getIsPublic()) {
                $items[$name] = $admin;
            }
        }
        return $items;
    }

    /**
     * Get admin classes of current module
     * @return Admin[]
     * @throws \Phact\Exceptions\ContainerException
     * @throws \Phact\Exceptions\NotFoundContainerException
     * @throws \ReflectionException
     */
    public function getAdmins(): array
    {
        $admins = [];
        $modulePath = $this->getPath();
        $path = implode(DIRECTORY_SEPARATOR, [$modulePath, $this->adminFolder]);
        if (is_dir($path)) {
            foreach (new RecursiveIteratorIterator(new RecursiveDirectoryIterator($path)) as $filename)
            {
                if ($filename->isDir()) continue;
                $name = $filename->getBasename('.php');
                $class = implode('\\', [static::classNamespace(), $this->adminFolder, $name]);
                $admins[$name] = $this->createAdmin($class, $name);
            }
        }
        return $admins;
    }

    /**
     * Make instance of Admin
     * @param $class
     * @param $name
     * @return Admin
     * @throws \Phact\Exceptions\ContainerException
     * @throws \Phact\Exceptions\NotFoundContainerException
     * @throws \ReflectionException
     */
    protected function createAdmin($class, $name): Admin
    {
        if ($app = Phact::app()) {
            return $app->getContainer()->construct($class, [
                $name,
                $this->getName()
            ]);
        }
        throw new \Exception(sprintf('Unable to load admin %s', $class));
    }
}