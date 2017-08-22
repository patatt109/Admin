<!doctype html>
<html lang="en">
<head>
    {if !$.request->getIsAjax()}
        <meta charset="utf-8">
        {* Title, description, keywords *}
        {block 'seo'}{/block}

        <link rel="stylesheet" href="/static/backend/dist/css/{$.backend_css_file('main')}">
        <script src="/static/backend/dist/js/{$.backend_js_file('main')}"></script>

        {* Another head information *}
        {block 'head'}{/block}
    {/if}
</head>
<body>
    <div class="wrapper">
        {if !$.request->getIsAjax()}
            {render_flash:raw template='admin/_flash.tpl'}

            {block 'menu_block'}
                <div class="menu-block">
                    <div class="links-block clearfix">
                        <a href="/" target="_blank" class="link"></a>
                        <a href="#" class="settings"></a>
                        <a href="{url route='admin:logout'}" class="logout"></a>
                    </div>
                    <div class="menu-wrapper">
                        <div class="search-block">
                            <input type="text" data-menu-search placeholder="Поиск...">
                        </div>
                        <ul class="main-menu">
                            {foreach $.admin_menu as $module}
                                <li class="module">
                                    <div class="name">
                                        {$module['name']}

                                        {if $module['settings']}
                                            <a href="{url 'admin:settings' [$module['key']]}" class="settings-link">
                                                <i class="icon-edit"></i>
                                            </a>
                                        {/if}
                                    </div>
                                    <ul class="items">
                                        {foreach $module['items'] as $item}
                                            <li class="item">
                                                <a href="{$item['route']}">
                                                    {$item['name']}
                                                </a>
                                            </li>
                                        {/foreach}
                                    </ul>
                                </li>
                            {/foreach}
                        </ul>
                    </div>
                </div>
            {/block}
        {/if}

        <div class="main-block {block 'main_block_class'}{/block}">
            {render_breadcrumbs:raw template="admin/_breadcrumbs.tpl"}

            {if $.block.heading}
                <div class="heading">
                    {block 'heading'}{/block}
                </div>
            {/if}

            {block 'main_block'}

            {/block}
        </div>
    </div>

    {block 'js'}

    {/block}
</body>
</html>