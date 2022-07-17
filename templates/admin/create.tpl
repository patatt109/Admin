{extends $.request->getIsAjax() ? "admin/ajax.tpl" : "admin/base.tpl"}

{block 'heading'}
    <h1>{t "Admin.main" "Creating"}</h1>
{/block}

{block 'main_block'}
    <div class="form-page {block 'page_class'}create{/block}">
        <form action="{$.request->getUrl()}" enctype="multipart/form-data" method="post">
            <div class="form-data">
                {include 'admin/form/_form.tpl'}
            </div>
            <div class="actions-panel">
                <div class="buttons">
                    <button type="submit" name="save" value="save" class="button pad round">
                        {t "Admin.main" "Save"}
                    </button>

                    {if !$.request->getIsAjax()}
                        <button type="submit" name="save" value="save-stay" class="button transparent pad round">
                            {t "Admin.main" "Save and continue"}
                        </button>

                        <button type="submit" name="save" value="save-create" class="button transparent pad round">
                            {t "Admin.main" "Save and create new"}
                        </button>
                    {/if}
                </div>

                <div class="links">
                    {if $model->pk && $admin->getViewUrl($model)}
                        <a href="{$admin->getViewUrl($model)}" target="_blank">
                            <i class="icon-watch_on_site"></i>
                            <span class="text">
                                {t "Admin.main" "Show on site"}
                            </span>
                        </a>
                    {/if}

                    {if !$.request->getIsAjax()}
                        {if $model->pk}
                            <a href="{$admin->getRemoveUrl($model->pk)}" data-all="{$admin->getAllUrl()}" data-prevention data-title="{t "Admin.main" "Do you really want to delete this object?"}" data-trigger="form-removed">
                                <i class="icon-delete_in_filter"></i>
                                <span class="text">
                                    {t "Admin.main" "Delete"}
                                </span>
                            </a>
                        {/if}
                    {/if}
                </div>
            </div>
        </form>
    </div>
{/block}
