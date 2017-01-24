{extends "admin/base.tpl"}

{block 'heading'}
    {var $treeParent = $admin->getTreeParent()}
    <h1>{$treeParent ? $treeParent : $admin->name}</h1>
{/block}

{block 'main_block'}
    <div class="all-page">
        {include 'admin/list/_list.tpl'}
    </div>
{/block}