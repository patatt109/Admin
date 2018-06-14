<div class="flash-messages-block">
    <ul class="flash-list"></ul>
</div>

{inline_js}
<script>
    $(function () {
        {foreach $messages as $item}
            window.addFlashMessage("{$item['message']}", "{$item['type']}");
        {/foreach}
    })
</script>
{/inline_js}