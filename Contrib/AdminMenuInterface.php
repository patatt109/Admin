<?php
/**
 *
 *
 * All rights reserved.
 *
 * @author Okulov Anton
 * @email qantus@mail.ru
 * @version 1.0
 * @date 05/10/2018 10:55
 */

namespace Modules\Admin\Contrib;

/**
 * Interface AdminMenuInterface
 * @package Modules\Admin\Contrib
 */
interface AdminMenuInterface
{
    /**
     * Admin menu items
     *
     * @return Admin[]
     */
    public function getAdmins(): array;

    /**
     * Admin menu items
     *
     * @return Admin[]
     */
    public function getPublicAdmins(): array;
}