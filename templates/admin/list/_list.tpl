{var $id = $admin->getId()}
{var $tree = $admin->getIsTree()}
{var $treeParent = $admin->getTreeParent()}
{var $related = $admin->ownerPk}

<div class="list-block" data-list data-id="{$id}-list">
    <div class="list-top clearfix">
        {if $admin->getForm()}
            <div class="top-buttons-block left">
                <a href="{$admin->getCreateUrl()}" class="{if $related}related-modal{/if} button round upper pad">
                    <span class="text">
                        Создать
                    </span>
                    <i class="icon-plus"></i>
                </a>
            </div>
        {/if}

        {if $search}
            <div class="top-search-block left">
                <input type="text" data-list-search placeholder="Поиск...">
            </div>
        {/if}
    </div>
    <div class="list-wrapper">
        <div class="list-update-block">
            <table data-list-table>
                <thead>
                    {var $cols = 0}

                    <tr class="list-head">
                        <th class="checker full">
                            <input type="checkbox" id="{$id}-check-all" data-checkall-list>
                            <label for="{$id}-check-all" class="alone"></label>
                            {var $cols = $cols+1}
                        </th>

                        {if $tree}
                            <th class="tree full">
                                {if $treeParent}
                                    <a href="{$admin->getAllUrl($treeParent ? $treeParent->parent_id : null)}">
                                        <i class="icon-folder"></i>
                                    </a>
                                {else}
                                    <i class="icon-folder"></i>
                                {/if}
                                {var $cols = $cols+1}
                            </th>
                        {/if}

                        {if $admin->getSortColumn()}
                            <th class="sort full" data-sort-column>
                                <span class="title">
                                     <i class="icon-double_triangle"></i>
                                </span>

                                {var $cols = $cols+1}
                            </th>
                        {/if}

                        {foreach $columns['enabled'] as $column}
                            {var $config = $columns['config'][$column]}
                            <th class="col full">
                                {include 'admin/list/_th.tpl'}
                                {var $cols = $cols+1}
                            </th>
                        {/foreach}

                        <th class="actions">
                            <div class="columns-list-appender">
                                <a href="#" class="button-appender appender-columns">
                                    <i class="icon-plus"></i>
                                </a>
                                <div class="popup-block">
                                    <ul class="columns-list">
                                        {foreach $columns['config'] as $name => $column}
                                            <li>
                                                <div class="checker">
                                                    <input type="checkbox" id="{$id}-{$name}-column" name="columns_list[]" value="{$name}" {if $name in $columns['enabled']}checked="checked"{/if}>
                                                    <label for="{$id}-{$name}-column">
                                                        {$column['title']}
                                                    </label>
                                                </div>
                                            </li>
                                        {/foreach}
                                    </ul>
                                </div>
                            </div>

                            {var $cols = $cols+1}
                        </th>
                    </tr>

                    <tr class="delimiter">
                        {foreach 1..$cols}
                            <th></th>
                        {/foreach}
                    </tr>
                </thead>
                <tbody>
                    {foreach $objects as $item}
                        {set $pk = $item->pk}
                        {set $update = false}
                        {if $pk in $admin->updateList}
                            {set $update = true}
                            {set $updateForm->prefix = $pk ~ '_'}
                            {do $updateForm->setInstance($item)}
                            {do $updateForm->setInstanceValues($item)}
                        {/if}
                        <tr data-pk="{$pk}" {if $update}data-formname="{$updateForm->getName()}"{/if} {if $tree and !$item->getIsLeaf()}data-children="{$admin->getAllUrl($pk)}"{/if} class="{if $update}updatable{/if}">
                            <td class="checker">
                                <input type="checkbox" id="{$id}-{$pk}-check" name="pk_list[]" value="{$pk}" {if $update}checked="checked"{/if}>
                                <label for="{$id}-{$pk}-check" class="alone"></label>
                            </td>

                            {if $tree}
                                <td class="tree">
                                    {if !$item->getIsLeaf()}
                                        <i class="icon-folder"></i>
                                    {/if}
                                </td>
                            {/if}

                            {if $admin->getSortColumn()}
                                <td class="sort">
                                    <a href="#" class="sort-handler {if $canSort}active{else}not-active{/if}">
                                        <i class="icon-double_triangle"></i>
                                    </a>
                                </td>
                            {/if}

                            {foreach $columns['enabled'] as $column}
                                {var $config = $columns['config'][$column]}
                                {var $template = $config['template']}

                                {if $update and $updateForm->hasField($column)}
                                    <td class="col updatable">
                                        {include 'admin/list/columns/update.tpl'}
                                    </td>
                                {else}
                                    <td class="col">
                                        {include $template}
                                    </td>
                                {/if}
                            {/foreach}

                            <td class="actions">
                                {include $admin->listItemActionsTemplate}
                            </td>
                        </tr>
                    {foreachelse}
                        <tr class="empty">
                            <td colspan="{$cols}" class="text-center">
                                Пока здесь нет ни одной записи
                            </td>
                        </tr>
                    {/foreach}
                </tbody>
            </table>
            <div class="list-footer clearfix">
                <div class="list-footer-block v-align right total">
                    <div>
                        Всего записей: {$pagination->getTotal()}
                    </div>
                </div>

                <div class="list-footer-block v-align left group">
                    <div>
                        {if $admin->updateList}
                            <a href="#" class="button round upper pad" data-group-save>
                                Сохранить изменения
                            </a>
                        {else}
                            <div class="checker-wrapper">
                                <input type="checkbox" id="{$id}-check-all-bottom" data-checkall-list>
                                <label for="{$id}-check-all-bottom">
                                    Для всех
                                </label>
                            </div>

                            {var $actions = $admin->getListGroupActions()}
                            {if ("update" in $actions) || ("remove" in $actions)}
                                <div class="group-buttons">
                                    {if ("update" in $actions)}
                                        <a href="#" class="group-button" data-group-update>
                                            <i class="icon-edit"></i>
                                        </a>
                                    {/if}

                                    {if ("remove" in $actions)}
                                        <a href="#" class="group-button" data-group-remove>
                                            <i class="icon-delete_in_table"></i>
                                        </a>
                                    {/if}
                                </div>
                            {/if}

                            {var $dropdown = $admin->getListDropDownGroupActions()}
                            {if $dropdown}
                                <div class="dropdown-block">
                                    <select name="" id="" data-group-action>
                                        <option value="" selected disabled>Выберите действие</option>
                                        {foreach $dropdown as $key => $item}
                                            <option value="{$key}">
                                                {$item['title']}
                                            </option>
                                        {/foreach}
                                    </select>
                                    <button class="button" data-group-submit>
                                        <i class="icon-check_mark"></i>
                                    </button>
                                </div>
                            {/if}
                        {/if}
                    </div>
                </div>
            </div>

            <div class="pagination-block">
                {raw $pagination->render($admin->listPaginationTemplate)}
            </div>
        </div>
    </div>
</div>

<script>
    $(function () {
        $('[data-id="{$id}-list"]').adminList({
            name: "{$id}",
            url: "{$.request->getUrl()}",
            groupActionUrl: "{$admin->getGroupActionUrl()}",
            sortUrl: "{$admin->getSortUrl()}",
            columnsUrl: "{$admin->getColumnsUrl()}"
        });
    });
</script>