<?php
/**
 *
 *
 * All rights reserved.
 *
 * @author Okulov Anton
 * @email qantus@mail.ru
 * @version 1.0
 * @date 03/10/16 09:47
 */

namespace Modules\Admin\Helpers;

use Modules\Admin\Contrib\AdminMenuInterface;
use Phact\Application\ModulesInterface;
use Phact\Di\ComponentFetcher;
use Phact\Interfaces\AuthInterface;

class AdminHelper
{
    use ComponentFetcher;

    public static function getMenu()
    {
        $menu = [];
        /** @var ModulesInterface $modules */
        $modules = self::fetchComponent(ModulesInterface::class);
        $auth = self::fetchComponent(AuthInterface::class);
        $user = $auth->getUser();

        if ($modules) {
            foreach ($modules->getModules() as $name => $module) {
                $items = [];
                if ($module instanceof AdminMenuInterface) {
                    $items = $module->getPublicAdmins();
                }
                $settings = $module->getSettingsModel();
                if ($user->getIsStaff() && empty($module->staffHasRule)) {
                    continue;
                }
                if ($items || $settings) {
                    $menu[] = [
                        'name' => $module->getVerboseName(),
                        'settings' => $settings,
                        'key' => $name,
                        'class' => get_class($module),
                        'items' => $items
                    ];
                }
            }

        }

        return $menu;
    }
}