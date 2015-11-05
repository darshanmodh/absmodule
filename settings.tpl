<fieldset>
    <legend>Settings</legend>
    <form method="post">
        <p>
            <label for="MOD_ABS_URL">URL:</label>
            <input id="MOD_ABS_URL" name="MOD_ABS_URL" type="text" value="{$MOD_ABS_URL}" />
        </p>
        <p>
            <label for="MOD_ABS_USERNAME">User Name:</label>
            <input id="MOD_ABS_USERNAME" name="MOD_ABS_USERNAME" type="text" value="{$MOD_ABS_USERNAME}" />
        </p>
        <p>
            <label for="MOD_ABS_PASSWORD">Password:</label>
            <input id="MOD_ABS_PASSWORD" name="MOD_ABS_PASSWORD" type="password" value="{$MOD_ABS_PASSWORD}" />
        </p>
        {*<p>
        <label for="MOD_ABS_CATEGORY">Category:</label>
            <input id="MOD_ABS_CATEGORY" name="MOD_ABS_CATEGORY" type="text" value="{$MOD_ABS_CATEGORY}" />
            </p>*}
        <p>
            <label>&nbsp;</label>
            <input id="submitAbs" name="submit_Abs" type="submit" value="Save" class="button" />
        </p>
    </form>
</fieldset>