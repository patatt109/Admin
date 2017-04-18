{if $column == '(string)'}
    {var $value = $.php.strval($item)}
{else}
    {var $value = $admin->getItemProperty($item, $column)}
{/if}

{if $item->hasField($column)}
    {set $field = $item->getField($column)}
    {if $field->choices}
        {$field->getChoiceDisplay()}
    {else}
        {$value}
    {/if}
{elseif $value|is_array}
    {$value|join:", "}
{else}
    {$value}
{/if}
