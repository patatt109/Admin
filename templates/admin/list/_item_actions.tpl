{var $actions = $admin->getListItemActions()}

{if $admin->getIsTree() && "create" in $actions}
    <a href="{$admin->getCreateUrl($pk)}">
        <i class="icon-plus"></i>
    </a>
{/if}

{if "update" in $actions}
    <a href="{$admin->getUpdateUrl($pk)}" class="{if $admin->ownerPk}related-modal{/if}">
        <i class="icon-edit"></i>
    </a>
{/if}

{if ("view" in $actions) && $admin->getViewUrl($item)}
    <a href="{$admin->getViewUrl($item)}">
        <i class="icon-search_mark"></i>
    </a>
{/if}

{if "info" in $actions}
    <a href="{$admin->getInfoUrl($pk)}">
        <i class="icon-info"></i>
    </a>
{/if}

{if "remove" in $actions}
    <a href="{$admin->getRemoveUrl($pk)}" data-prevention data-title="{t "Admin.main" "Do you really want to delete this object?"}" data-trigger="list-update">
        <i class="icon-delete_in_table"></i>
    </a>
{/if}