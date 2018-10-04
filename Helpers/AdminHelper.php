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

use Phact\Application\ModulesInterface;
use Phact\Di\ComponentFetcher;

class AdminHelper
{
    use ComponentFetcher;

    public static function getMenu()
    {
        $menu = [];
        /** @var ModulesInterface $modules */
        $modules = self::fetchComponent(ModulesInterface::class);

        if ($modules) {
            foreach ($modules->getModulesClasses() as $name => $class) {
                $moduleMenu = $class::getAdminMenu();
                $settings = $class::getSettingsModel();
                if ($moduleMenu || $settings) {
                    $menu[] = [
                        'name' => $class::getVerboseName(),
                        'settings' => $settings,
                        'key' => $name,
                        'class' => $class,
                        'items' => $moduleMenu
                    ];
                }
            }
        }

        return $menu;
    }
}