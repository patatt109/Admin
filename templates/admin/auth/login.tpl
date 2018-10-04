{extends "admin/base.tpl"}

{block 'menu_block'}
{/block}

{block 'main_block_class'}wide{/block}

{block 'main_block'}
    <div class="login-page">
        <div class="login-block">
            <h1>{t "Admin.auth" "Login"}</h1>

            <form action="" method="post">
                {raw $form->render()}

                <div class="buttons">
                    <button type="submit" class="button round default">
                        {t "Admin.auth" "Log in"}
                    </button>
                </div>
            </form>
        </div>
    </div>
{/block}