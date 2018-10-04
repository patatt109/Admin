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

{set $boundAdmins = $admin->getInitBoundAdmins()}
{if $boundAdmins}
    <div class="bound-admins">
        {foreach $boundAdmins as $boundAdmin}
            <fieldset>
                <div class="fieldset-title">
                    {$boundAdmin->getItemName()}
                </div>
                {var $boundForm = $boundAdmin->getCurrentForm()}
                {var $fields = $boundForm->getInitFields()}
                <div class="fields">
                    {foreach $fields as $field}
                        <div class="form-field {$field->name}">
                            {raw $field->render()}
                        </div>
                    {/foreach}
                </div>
            </fieldset>
        {/foreach}
    </div>
{/if}

{var $relatedAdmins = $admin->getInitRelatedAdmins()}
{if $relatedAdmins}
    <div class="related-admins">
        {foreach $relatedAdmins as $relatedAdmin}
            <div class="related-admin">
                <h2 class="title">
                    {$relatedAdmin->getName()}
                </h2>

                {if $relatedAdmin->ownerPk}
                    {$relatedAdmin->all('admin/list/_list.tpl')}
                {else}
                    <div class="unsaved-error">
                        {t "Admin.main" "Please save the object to work with this data"}
                    </div>
                {/if}
            </div>
        {/foreach}
    </div>
{/if}