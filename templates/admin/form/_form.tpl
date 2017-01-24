{var $fieldsets = $admin->getFormFieldsets()}
{if $fieldsets}
    {foreach $fieldsets as $name => $fieldsNames}
        <fieldset>
            <div class="fieldset-title">
                {$name}
            </div>
            <div class="fields">
                {foreach $fieldsNames as $fieldName}
                    {var $field = $form->getField($fieldName)}
                    <div class="form-field {$fieldName}">
                        {raw $field->render()}
                    </div>
                {/foreach}
            </div>
        </fieldset>
    {/foreach}
{else}
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
{/if}