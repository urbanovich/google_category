<?php

/*
* 2007-2016 PrestaShop
*
* NOTICE OF LICENSE
*
* This source file is subject to the Academic Free License (AFL 3.0)
* that is bundled with this package in the file LICENSE.txt.
* It is also available through the world-wide-web at this URL:
* http://opensource.org/licenses/afl-3.0.php
* If you did not receive a copy of the license and are unable to
* obtain it through the world-wide-web, please send an email
* to license@prestashop.com so we can send you a copy immediately.
*
*         DISCLAIMER   *
* *************************************** */

/* Do not edit or add to this file if you wish to upgrade Prestashop to newer
* versions in the future.
* *****************************************************
* @category   Belvg
* @package    google_category.php
* @author     Dzmitry Urbanovich (urbanovich.mslo@gmail.com)
* @site       http://module-presta.com
* @copyright  Copyright (c) 2007 - 2016 BelVG LLC. (http://www.belvg.com)
* @license    http://store.belvg.com/BelVG-LICENSE-COMMUNITY.txt
*/

if(!defined('_PS_VERSION_'))
    exit;

define('GC_DOWNLOAD_DIR', _PS_MODULE_DIR_ . 'google_category/download/');

//register autoload
require_once _PS_MODULE_DIR_ . 'google_category/autoloadGoogleCategory.php';

class google_category extends Module
{

    public $_hooks = array(
        'moduleRoutes',
        'beforeDispatcher',
        'displayAdminProductsExtra',
        'actionProductSave',
    );

    public function __construct()
    {
        $this->name = 'google_category';
        $this->tab = 'front_office_features';
        $this->version = '1.0.0';
        $this->author = 'Belvg';
        $this->need_instance = 0;
        $this->ps_versions_compliancy = array('min' => '1.6', 'max' => _PS_VERSION_);
        $this->bootstrap = true;

        parent::__construct();

        $this->displayName = $this->l('Google category');
        $this->description = $this->l('Google category.');

        $this->confirmUninstall = $this->l('Are you sure you want to uninstall?');

        if (!Configuration::get('Google category'))
            $this->warning = $this->l('No name provided');
    }

    public function install()
    {
        if (!InstallGoogleCategory::execute()
            || !$this->createDownloadDir()
            || !$this->registerHook($this->_hooks)
            || !parent::install())
            return false;

        //create admin page for list custom pages
        $id_tab = Tab::getIdFromClassName('AdminModules');
        if (!$this->installModuleTab('AdminGoogleCategory', $this->displayName, $id_tab)
            || !$this->installModuleTab('AdminBatch', 'AdminBatch', 0, 0))
            return false;

        return true;
    }

    public function uninstall()
    {

        if (!$this->deleteDownloadDir()
            || !$this->uninstallModuleTab('AdminGoogleCategory')
            || !$this->uninstallModuleTab('AdminBatch')
            || !UninstallGoogleCategory::execute()
            || !parent::uninstall())
            return false;

        //unregister hooks
        if (isset($this->_hooks) && !empty($this->_hooks))
        {
            foreach ($this->_hooks as $hook)
            {
                if (!empty($hook) && !$this->unregisterHook($hook))
                {
                    return false;
                }
            }
        }

        return true;
    }

    public function hookDisplayAdminProductsExtra()
    {
        // Get default language
        $default_lang = (int)Configuration::get('PS_LANG_DEFAULT');

        $google_category_product = GoogleCategoryProduct::getProductById(Tools::getValue('id_product'));
        $selected_categories = array();

        foreach($google_category_product as $product)
        {
            $selected_categories[] = $product['id_google_category'];
        }

        // Init Fields form array
        $fields_form[0]['form'] = array(
            'legend' => array(
                'title' => $this->l('If the customer already did an order in the last XX days'),
                'icon' => 'icon-tags'
            ),
            'input' => array(
                array(
                    'type'  => 'categories',
                    'label' => $this->l('Parent category'),
                    'name'  => 'id_google_category',
                    'tree'  => array(
                        'id'                  => 'google-categories-tree',
                        'selected_categories' => $selected_categories,
                        'disabled_categories' => null,
                        'root_category'       => '0',
                        'set_data' => GoogleCategory::getNestedCategories(),
                        'use_checkbox' => true,
                        'use_search' => true,
                    )
                ),
            ),
            'submit' => array(
                'title' => $this->l('Save'),
                'name' => 'submitAddproduct',
                'class' => 'btn btn-default pull-right'
            )
        );

        $helper = new HelperForm();

        // Module, token and currentIndex
        $helper->module = $this;
        $helper->name_controller = $this->name;
        $helper->token = Tools::getAdminTokenLite('AdminModules');
        $helper->currentIndex = AdminController::$currentIndex.'&configure='.$this->name;

        // Language
        $helper->default_form_language = $default_lang;
        $helper->allow_employee_form_lang = $default_lang;

        // Title and toolbar
        $helper->title = $this->displayName;
        $helper->show_toolbar = false;        // false -> remove toolbar
        $helper->toolbar_scroll = false;      // yes - > Toolbar is always visible on the top of the screen.
        $helper->submit_action = 'submit'.$this->name;
        $helper->toolbar_btn = array(
            'save' =>
                array(
                    'desc' => $this->l('Save'),
                    'href' => AdminController::$currentIndex.'&configure='.$this->name.'&save'.$this->name.
                        '&token='.Tools::getAdminTokenLite('AdminModules'),
                ),
            'back' => array(
                'href' => AdminController::$currentIndex.'&token='.Tools::getAdminTokenLite('AdminModules'),
                'desc' => $this->l('Back to list')
            )
        );

        $form = $helper->generateForm($fields_form);
        $form = preg_replace('/<form.*?>|<\/form>/', '', $form);

        $js = '<script>
                $(function(){
                    $(".tree-panel-label-title").remove();
                    $(".tree-actions").remove();
                    $("#category-tree-toolbar").show();
                    $("#block_category_tree").show();
                });
            </script>';

        return $form . $js;
    }

    public function hookActionProductSave($params)
    {

        if(!Tools::isSubmit('id_google_category'))
            return;

        $id_product = $params['id_product'];

        GoogleCategoryProduct::deleteProducts($id_product);

        foreach(Tools::getValue('id_google_category') as $id_google_category)
        {
            $google_category_product = new GoogleCategoryProduct();
            $google_category_product->id_product = $id_product;
            $google_category_product->id_google_category = $id_google_category;

            $google_category_product->save();
        }
    }

    public function createDownloadDir()
    {

        if(!file_exists(GC_DOWNLOAD_DIR))
        {
            return mkdir(GC_DOWNLOAD_DIR, 0777);
        }

        $this->_errors[] = $this->displayError('You need to set the directory ' . GC_DOWNLOAD_DIR . ' to record(0777) and repeat the setting');

        return false;
    }

    public function deleteDownloadDir()
    {
        if(file_exists(GC_DOWNLOAD_DIR))
        {
            return Tools::deleteDirectory(GC_DOWNLOAD_DIR);
        }

        $this->_errors[] = $this->displayError('I can not remove a directory ' . GC_DOWNLOAD_DIR);

        return false;
    }

    /**
     * Add new page in back office
     *
     * @param type $class_name
     * @param type $tab_name
     * @param type $id_parent
     * @param type $position
     *
     * @return type
     */
    public function installModuleTab($class_name, $tab_name, $id_parent = 0, $position = 0)
    {

        $tab = new Tab();
        $tab->active = 1;
        $tab->class_name = $class_name;
        $tab->name = array();

        foreach (Language::getLanguages(true) as $lang)
            $tab->name[$lang['id_lang']] = $tab_name;

        $tab->id_parent = $id_parent;
        $tab->position = $position;
        $tab->module = $this->name;

        return $tab->save();
    }

    /**
     * Delete custom page of back office
     *
     * @param type $class_name
     *
     * @return type
     */
    public function uninstallModuleTab($class_name)
    {

        $id_tab = Tab::getIdFromClassName($class_name);

        if ($id_tab) {

            $tab = new Tab($id_tab);
            $tab->delete();
            return true;
        }

        return false;
    }
}