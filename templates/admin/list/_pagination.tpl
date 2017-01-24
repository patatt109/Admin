{extends 'pagination/default.tpl'}

{block 'prev'}
    <i class="icon-paginator_arrow_left"></i>
{/block}

{block 'next'}
    <i class="icon-paginator_arrow_right"></i>
{/block}

{block 'before_pagination_block'}
    <div class="select-block right">
        <select data-pagesize>
            {var $pageSize = $pagination->fetchPageSize()}
            {var $pageSizeKey = $pagination->getRequestPageSizeKey()}
            {foreach $pagination->pageSizes as $size}
                <option value="{build_url data=[$pageSizeKey => $size]}" {if $size == $pageSize}selected="selected"{/if}>
                    {$size}
                </option>
            {/foreach}
        </select>
    </div>
{/block}