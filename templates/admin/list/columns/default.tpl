{if $column == '(string)'}
    {var $value = $.php.strval($item)}
{else}
    {var $value = $admin->getItemProperty($item, $column)}
{/if}

{if $value is \Phact\Orm\Fields\FileField}
    {set $url = $value->getUrl()}
    {if $url}
        <a href="{$url}" target="_blank">
            {$url}
        </a>
    {/if}
{elseif $value|is_array}
    {$value|join:", "}
{elseif $item->hasField($column)}
    {set $field = $item->getField($column)}
    {if $field->choices}
        {$field->getChoiceDisplay()}
    {else}
        {$value}
    {/if}
{else}
    {$value}
{/if}