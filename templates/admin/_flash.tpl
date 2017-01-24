<div class="flash-messages-block">
    <ul class="flash-list"></ul>
</div>

<script>
    $(function () {
        {foreach $messages as $item}
            window.addFlashMessage("{$item['message']}", "{$item['type']}");
        {/foreach}
    })
</script>