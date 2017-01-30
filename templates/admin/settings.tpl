{extends $.request->getIsAjax() ? "admin/ajax.tpl" : "admin/base.tpl"}

{block 'heading'}
    <h1>Настройки модуля "{$settingsModule->getVerboseName()}"</h1>
{/block}

{block 'main_block'}
    <div class="form-page settings">
        <form action="{$.request->getUrl()}" enctype="multipart/form-data" method="post">
            <div class="form-data">
                <fieldset>
                    {var $fields = $form->getInitFields()}
                    <div class="fields">
                        {foreach $fields as $field}
                            <div class="form-field {$field->name}">
                                {raw $field->render()}
                            </div>
                        {/foreach}
                    </div>
                </fieldset>
            </div>
            <div class="actions-panel">
                <div class="buttons">
                    <button type="submit" name="save" value="save" class="button pad round">
                        Сохранить
                    </button>
                </div>
            </div>
        </form>
    </div>
{/block}
