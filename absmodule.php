<?php

//

if (!defined('_PS_VERSION_'))
    exit;
include_once __DIR__ . '/php-demo/import.php';

class AbsModule extends Module {

    public function __construct() {
        $this->name = 'absmodule';
        $this->tab = 'administration';
        $this->version = '1.0';
        $this->author = 'Sanket Sharma';
        $this->need_instance = 0;
        $this->ps_versions_compliancy = array('min' => '1.5', 'max' => '1.6');

        parent::__construct();

        $this->displayName = $this->l('ABS Module');
        $this->description = $this->l('This module is used for importing ABS products into prestashop.');

        $this->confirmUninstall = $this->l('Are you sure you want to uninstall?');

        $sql = "CREATE TABLE IF NOT EXISTS `" . _DB_PREFIX_ . "abs`(
	    `id_abs` INT(11) NOT NULL AUTO_INCREMENT PRIMARY KEY ,
	    `url` VARCHAR(256) NOT NULL ,
            `username` VARCHAR(32) NOT NULL ,
            `password` VARCHAR(32) NOT NULL ,
            `category` VARCHAR(20) ,
            `key` VARCHAR(32) NOT NULL ,
             unique index( `url`,`username`)
            )";

        if (!$result = Db::getInstance()->Execute($sql)) {
            $this->warning = "Database didnot create. Please reconfigure the module.";
        }

        if (!Configuration::get('ABSMODULE_NAME'))
            $this->warning = $this->l('No name provided');
    }

//    public function install() {
//        if (parent :: install() == false && Configuration::updateValue('ABSMODULE_NAME', 'Abs') &&
//                $this->registerHook('leftColumn'))
//            return false;
//        if (Shop::isFeatureActive())
//            Shop::setContext(Shop::CONTEXT_ALL);
//        return true;
//    }
    public function install() {
        if (Shop::isFeatureActive())
            Shop::setContext(Shop::CONTEXT_ALL);

        return parent::install() &&
                $this->registerHook('backOfficeFooter') &&
                Configuration::updateValue('ABSMODULE_NAME', 'Abs');
    }

    public function uninstall() {
        if (!parent::uninstall() ||
                !$this->_deleteContent())
            return false;
        return true;
    }

    private function _deleteContent() {
        $sql = "DROP TABLE `" . _DB_PREFIX_ . "abs`";
        if (!Configuration ::deleteByName('ABSMODULE_NAME') || !$result = Db::getInstance()->Execute($sql))
            return false;
        return true;
    }

    public function getContent() {
        $message = '';

        if (Tools::isSubmit('submit_Abs')) {
            $abs_url = Tools::getValue('MOD_ABS_URL');
            $abs_username = Tools::getValue('MOD_ABS_USERNAME');
            $abs_password = Tools::getValue('MOD_ABS_PASSWORD');
            $message = $this->_saveContent($abs_url, $abs_username, $abs_password);
            if ($message == 'error') {
                $response = $this->displayError($this->l('There was an error while saving your settings.'));
                $key = NULL;
            } else {
                $response = $this->displayConfirmation($this->l('Your Settings have been saved.'));
                $key = $message;
            }
            //$message = $this->displayConfirmation($this->l('Abs configured Succesfully.'));

            $this->context->smarty->assign(array(
                'message' => $response,
                'key' => $key
            ));

            return $this->display(__FILE__, 'display.tpl');
        }

        $this->_displayContent($message);

        return $this->display(__FILE__, 'settings.tpl');
    }

    private function _saveContent($abs_url, $abs_username, $abs_password) {
        $message = 'Hello';
        $id_abs = (int) Db::getInstance()->getValue('SELECT MAX(id_abs) FROM ' . _DB_PREFIX_ . 'abs');
        $id_abs += 1;
        $id_category = (int) Db::getInstance()->getValue('SELECT id_category FROM ' . _DB_PREFIX_ . 'category_lang WHERE name="ABS Products"');
        //$id_category = $id_category?new Category((int)$id_category,TRUE):new Category();
        if ($id_category != 0) {
            $category = new Category((int) $id_category, TRUE);
        } else {
            $category = new Category();
            $category->name[1] = 'ABS Products';
            $category->id_parent = 2;
            $category->link_rewrite[1] = Tools::link_rewrite('abs products');
            $category->active = 1;
            $category->save();
        }        
        $key = $abs_username . '_' . $id_abs;

        try {
            Db::getInstance()->insert('abs', array(
                'url' => pSQL($abs_url),
                'username' => pSQL($abs_username),
                'password' => pSQL($abs_password),
                'category' => pSQL($category->id),
                'key' => pSQL($key)
            ));
            $message = $key;
        } catch (PrestaShopDatabaseException $e) {
            $message = 'error';
        }
        return $message;
    }

    private function _displayContent($message, $abs_url = 'Url', $abs_username = 'user_name', $abs_password = 'password') {
        $this->context->smarty->assign(array(
            'message' => $message,
            'MOD_ABS_URL' => $abs_url,
            'MOD_ABS_USERNAME' => $abs_username,
            'MOD_ABS_PASSWORD' => $abs_password));
    }

    private function _saveContentABS() {
        $ans = "";
        if (Tools::isSubmit('importBtn')) {
            $key = Tools::getValue("MOD_ABS_KEY");
            $url = Db::getInstance()->getValue('SELECT `url` FROM ' . _DB_PREFIX_ . 'abs WHERE `key` = \'' . pSQL($key) . '\'');
            if ($url != NULL) {
                $username = Db::getInstance()->getValue('SELECT `username` FROM ' . _DB_PREFIX_ . 'abs WHERE `key` = \'' . pSQL($key) . '\'');
                $password = Db::getInstance()->getValue('SELECT `password` FROM ' . _DB_PREFIX_ . 'abs WHERE `key` = \'' . pSQL($key) . '\'');
                $category_id = Db::getInstance()->getValue('SELECT `category` FROM ' . _DB_PREFIX_ . 'abs WHERE `key` = \'' . pSQL($key) . '\'');
                $abs = new ABS();
                $ans = $abs->execute($url, $username, $password, $category_id);
            } else {
                $ans = 'Key is incorrect.';
            }
        }
        return $ans;
    }

    public function hookDisplayBackOfficeFooter($params) {
        $msg = $this->_saveContentABS();
        $this->context->smarty->assign(
                array(
                    'my_module_name' => Configuration::get('ABSMODULE_NAME'),
                    'my_module_link' => $this->context->link->getModuleLink('absmodule', 'display'),
                    'ans' => $msg
                )
        );
        return $this->display(__FILE__, 'absmodule.tpl');
    }

}
