<?php

return [
    [
        'route' => '',
        'target' => [\Modules\Admin\Controllers\CommonController::class, 'index'],
        'name' => 'index'
    ],

    [
        'route' => '/all/{:module}/{:admin}',
        'target' => [\Modules\Admin\Controllers\AdminController::class, 'all'],
        'name' => 'all'
    ],
    [
        'route' => '/all/{:module}/{:admin}/{:parentId}',
        'target' => [\Modules\Admin\Controllers\AdminController::class, 'all'],
        'name' => 'all_children'
    ],
    [
        'route' => '/create/{:module}/{:admin}',
        'target' => [\Modules\Admin\Controllers\AdminController::class, 'create'],
        'name' => 'create'
    ],
    [
        'route' => '/create/{:module}/{:admin}/{:parentId}',
        'target' => [\Modules\Admin\Controllers\AdminController::class, 'create'],
        'name' => 'create_child'
    ],
    [
        'route' => '/group_action/{:module}/{:admin}',
        'target' => [\Modules\Admin\Controllers\AdminController::class, 'groupAction'],
        'name' => 'group_action'
    ],
    [
        'route' => '/sort/{:module}/{:admin}',
        'target' => [\Modules\Admin\Controllers\AdminController::class, 'sort'],
        'name' => 'sort'
    ],
    [
        'route' => '/sort/{:module}/{:admin}/{:parentId}',
        'target' => [\Modules\Admin\Controllers\AdminController::class, 'sort'],
        'name' => 'sort_children'
    ],
    [
        'route' => '/columns/{:module}/{:admin}',
        'target' => [\Modules\Admin\Controllers\AdminController::class, 'columns'],
        'name' => 'columns'
    ],
    [
        'route' => '/update/{:module}/{:admin}/{:pk}',
        'target' => [\Modules\Admin\Controllers\AdminController::class, 'update'],
        'name' => 'update'
    ],
    [
        'route' => '/info/{:module}/{:admin}/{:pk}',
        'target' => [\Modules\Admin\Controllers\AdminController::class, 'info'],
        'name' => 'info'
    ],
    [
        'route' => '/remove/{:module}/{:admin}/{:pk}',
        'target' => [\Modules\Admin\Controllers\AdminController::class, 'remove'],
        'name' => 'remove'
    ],
    [
        'route' => '/login',
        'target' => [\Modules\Admin\Controllers\AuthController::class, 'login'],
        'name' => 'login'
    ],
    [
        'route' => '/logout',
        'target' => [\Modules\Admin\Controllers\AuthController::class, 'logout'],
        'name' => 'logout'
    ],
];