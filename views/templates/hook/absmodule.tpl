<!-- Block mymodule -->


<div id="mymodule_block_left" class="block">
    <h4>Welcome!</h4>
    <div class="block_content">
        <p>Hello,
            {if isset($my_module_name) && $my_module_name}
                {$my_module_name}
            {else}
                Reinstall this module.
            {/if}
            !
        </p>
        <fieldset>
            <legend>Importing from ABS</legend>
            <form method="post" >
                <p>
                    <label>{$ans}</label>
                    <br/>
                    <label>Enter Key:</label>
                    <input id="MOD_ABS_KEY" name="MOD_ABS_KEY" value="" type="text" /><br/>
                    <label>Click here to import</label>                    
                    <input id="importBtn" name="importBtn" type="submit" value="Import" />
                </p>
            </form>
        </fieldset>

    </div>
</div>
<!-- /Block mymodule -->