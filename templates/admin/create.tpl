{extends "admin/base.tpl"}

{block 'heading'}
    <h1>Добавление</h1>
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
                        Сохранить
                    </button>

                    <button type="submit" name="save" value="save-stay" class="button transparent pad round">
                        Сохранить и продолжить
                    </button>

                    <button type="submit" name="save" value="save-create" class="button transparent pad round">
                        Сохранить и создать
                    </button>
                </div>

                <div class="links">
                    {if $model->pk && $.php.method_exists($model, 'getAbsoluteUrl')}
                        <a href="{$model->getAbsoluteUrl()}">
                            <i class="icon-watch_on_site"></i>
                            <span class="text">
                                Посмотреть на сайте
                            </span>
                        </a>
                    {/if}

                    {if $model->pk}
                        <a href="{$admin->getRemoveUrl($model->pk)}" data-all="{$admin->getAllUrl()}" data-prevention data-title="Вы действительно хотите удалить данный объект?" data-trigger="form-removed">
                            <i class="icon-delete_in_filter"></i>
                            <span class="text">
                                Удалить
                            </span>
                        </a>
                    {/if}
                </div>
            </div>
        </form>
    </div>
{/block}
