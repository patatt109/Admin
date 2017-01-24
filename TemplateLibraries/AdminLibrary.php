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
 * @date 03/10/16 10:40
 */

namespace Modules\Admin\TemplateLibraries;

use Modules\Admin\Helpers\AdminHelper;
use Phact\Template\TemplateLibrary;

class AdminLibrary extends TemplateLibrary
{
    /**
     * @kind accessorProperty
     * @name admin_menu
     * @return array
     */
    public static function getMenu()
    {
        return AdminHelper::getMenu();
    }
}