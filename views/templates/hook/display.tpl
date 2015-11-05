{$message}


<!-- Block mymodule -->


<div id="mymodule_block_left" class="block">
    <h4>Welcome new abs user!</h4>
    <div class="block_content">
        <center>
            <p>
                {if isset($key) && $key}
                    Use this key While imoprting. Please store this key.<br/>
                    Key is : <b>{$key}</b> <br/>
                    <br/>
                    All your products will be imoprted to ABS Products category. Thank you..

                {else}
                    Username and Url already exist.
                {/if}
                !
            </p>
        </center>
    </div>
</div>
<!-- /Block mymodule -->