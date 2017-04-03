{if $column == '(string)'}
    {var $value = $.php.strval($item)}
{else}
    {var $value = $admin->getItemProperty($item, $column)}
{/if}

{if $value|is_array}
    {$value|join:", "}
{else}
    {$value}
{/if}