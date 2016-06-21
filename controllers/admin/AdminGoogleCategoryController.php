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
* @package    AdminFriendlyUrls.php
* @author     Dzmitry Urbanovich (urbanovich.mslo@gmail.com)
* @site       http://module-presta.com
* @copyright  Copyright (c) 2007 - 2016 BelVG LLC. (http://www.belvg.com)
* @license    http://store.belvg.com/BelVG-LICENSE-COMMUNITY.txt
*/

class AdminGoogleCategoryController extends ModuleAdminController
{

    public function __construct()
    {
        $this->bootstrap = true;
        $this->table = 'google_category';
        $this->className = 'GoogleCategory';
        $this->lang = false;
        $this->deleted = false;
        $this->explicitSelect = true;
        $this->allow_export = true;

        $this->context = Context::getContext();

        $this->fields_list = array(
            'id_google_category' => array(
                'title' => $this->l('ID'),
                'align' => 'center',
                'class' => 'fixed-width-xs'
            ),
            'name' => array(
                'title' => $this->l('Google category'),
                'callback' => 'getSubStrText',
            ),
            'active' => array(
                'title' => $this->l('Displayed'),
                'active' => 'status',
                'type' => 'bool',
                'class' => 'fixed-width-xs',
                'align' => 'center',
                'ajax' => true,
                'orderby' => false
            )
        );

        $this->bulk_actions = array(
            'delete' => array(
                'text' => $this->l('Delete selected'),
                'icon' => 'icon-trash',
                'confirm' => $this->l('Delete selected items?')
            )
        );
        $this->specificConfirmDelete = false;

        parent::__construct();

        $this->_where .= ' AND id_google_category != 0';

    }

    public function init() {
        parent::init();

        $id_category = GoogleCategory::getIdCategory(Tools::getValue('id_google_category', 0));
        if($id_category) {
            $this->original_filter = $this->_filter .= ' AND `id_parent` = ' . (int)$id_category . ' ';
        } else {
            $this->original_filter = $this->_filter .= ' AND `id_parent` = 0 ';
        }

        if(isset($_FILES['google_category']) && !$_FILES['google_category']['error'])
        {
            if($_FILES['google_category']['type'] == 'application/vnd.ms-excel') {

                //delete google category if update
                if(Db::getInstance()->getValue('SELECT COUNT(id_google_category) FROM ' . _DB_PREFIX_ . 'google_category;') > 0)
                    Db::getInstance()->execute('DELETE FROM ' . _DB_PREFIX_ . 'google_category WHERE 1;');

                //delete a old files
                $upload_files = scandir(GC_DOWNLOAD_DIR);
                foreach($upload_files as $file)
                {
                    if(strpos($file, '.') !== 0)
                        Tools::deleteFile(GC_DOWNLOAD_DIR . $file);
                }

                $helper = new HelperUploader($_FILES['google_category']['name']);
                $helper->setPostMaxSize(Tools::getOctets(ini_get('upload_max_filesize')))
                    ->setSavePath(GC_DOWNLOAD_DIR)->upload($_FILES['google_category'], $_FILES['google_category']['name']);

                //set permissions 777
                chmod(GC_DOWNLOAD_DIR . $_FILES['google_category']['name'], 0777);

                $upload_files = scandir(GC_DOWNLOAD_DIR);
                $upload_file = '';
                foreach($upload_files as $file)
                {
                    if(strpos($file, '.') !== 0)
                        $upload_file = GC_DOWNLOAD_DIR . $file;
                }

                $google_category = $this->getXlsFileParams($upload_file);

                $id_parents = array();
                $id = 0;
                foreach($google_category as $category)
                {
                    foreach($category as $key => $cat)
                    {
                        if(is_numeric($cat))
                            $id = $cat;

                        if(!isset($id_parents[$cat]) && !is_numeric($cat))
                            $id_parents[$cat] = $id;

                    }
                }

                $import_categories = array();
                foreach($google_category as $category)
                {
                    $name = $category[count($category) - 1];
                    $parent_name = $category[count($category) - 2];
                    $import_categories[]['AdminGoogleCategoryController::batch'] = array(
                        'id_google_category' => $category[1],
                        'id_parent' => isset($id_parents[$parent_name]) ? $id_parents[$parent_name] : 0 ,
                        'name' => $name,
                    );
                }

                $batch = new Batch();
                $batch->count = 50;
                $batch->start = 0;
                $batch->set($import_categories, $this->module->getLocalPath() . 'controllers/admin/AdminGoogleCategoryController.php')
                    ->start();

            } else {
                $this->errors[] = '[' . $_FILES['google_category']['name'] . '] ' . Tools::displayError('Format file only xls 2003 year.');
            }

        } elseif(isset($_FILES['google_category']) && $_FILES['google_category']['error'])
        {
            $this->error[] = Tools::displayError('Error upload a file ' . $_FILES['google_category']['name'] . ', error upload number: ' . $_FILES['google_category']['error'] . "\n" . '
                                please see: http://ua2.php.net/manual/ru/features.file-upload.errors.php');
        }
    }

    public function setMedia()
    {

        parent::setMedia();
        $this->addCSS(_PS_MODULE_DIR_ . 'google_category/views/css/google_category_admin.css', 'all');

        $this->addJqueryUI(array('ui.dialog', 'ui.progressbar'));
        $this->addJS(_PS_MODULE_DIR_ . 'google_category/views/js/google_category_admin.js');

        if($count = Db::getInstance()->getValue('SELECT COUNT(id) FROM ' . _DB_PREFIX_ . 'google_category_batch;'))
        {
            Media::addJsDef(array(
                'url' => $this->context->link->getAdminLink('AdminBatch') . '&action=batch&ajax=1',
                'start' => 0,
                'count' => 50,
                'percent' => $count,
                'all' => $count,
                'path_to_file' => $this->module->getLocalPath() . 'controllers/admin/AdminGoogleCategoryController.php',
            ));
        }

    }

    public function renderList()
    {
        if (isset($this->_filter) && trim($this->_filter) == '')
            $this->_filter = $this->original_filter;

        $this->addRowAction('view');
        $this->addRowAction('add');
        $this->addRowAction('edit');
        $this->addRowAction('delete');

        return $this->renderCreateForm() .
                parent::renderList();
    }

    public function renderView()
    {

        if(Tools::isSubmit('id_google_category') && Tools::isSubmit('update' . $this->table))
            return $this->renderForm();
        else
            return $this->renderList();
    }

    public static function batch($id, $params)
    {
        if(!$params['id_google_category'])
            return;

        $google_category = new GoogleCategory();
        $google_category->id_category = $params['id_google_category'];
        $google_category->id_parent = $params['id_parent'];
        $google_category->name = $params['name'];
        $google_category->active = true;

        $google_category->save();
    }

    public function renderForm()
    {

        if (!$obj = $this->loadObject(TRUE))
        {
            return;
        }

        $this->fields_form = array(
            'legend' => array(
                'title' => $this->l('Google Category'),
                'icon' => 'icon-tags'
            ),
            'input' => array(
                array(
                    'type' => 'text',
                    'label' => $this->l('Name'),
                    'name' => 'name',
                    'required' => true,
                    'hint' => $this->l('Forbidden characters:') . ' <>;=#{}'
                ),
                array(
                    'type'  => 'categories',
                    'label' => $this->l('Parent category'),
                    'name'  => 'id_parent',
                    'tree'  => array(
                        'id'                  => 'google-categories-tree',
                        'selected_categories' => array($obj->id_parent),
                        'disabled_categories' => null,
                        'root_category'       => 0,
                        'set_data' => GoogleCategory::getNestedCategories(),
                    )
                ),
                array(
                    'type' => 'switch',
                    'label' => $this->l('Displayed'),
                    'name' => 'active',
                    'required' => false,
                    'is_bool' => true,
                    'values' => array(
                        array(
                            'id' => 'active_on',
                            'value' => 1,
                            'label' => $this->l('Enabled')
                        ),
                        array(
                            'id' => 'active_off',
                            'value' => 0,
                            'label' => $this->l('Disabled')
                        )
                    )
                ),
            ),
            'submit' => array(
                'title' => $this->l('Save'),
                'name' => 'submitAdd' . $this->table,
            )
        );

        $js = '<script>
                $(function(){
                    $(".tree-panel-label-title").remove();
                    $(".tree-actions").remove();
                    $("#category-tree-toolbar").show();
                    $("#block_category_tree").show();
                });
            </script>';

        return parent::renderForm() . $js;
    }

    public function renderCreateForm()
    {
        // Get default language
        $default_lang = (int)Configuration::get('PS_LANG_DEFAULT');

        // Init Fields form array
        $fields_form[0]['form'] = array(
            'legend' => array(
                'title' => $this->l('If the customer already did an order in the last XX days'),
                'icon' => 'icon-tags'
            ),
            'input' => array(
                array(
                    'type' => 'file',
                    'label' => $this->l('Google category xls'),
                    'name' => 'google_category',
                ),
                array(
                    'type' => 'html',
                    'name' => 'FU_PROCESS_BAR',
                    'html_content' => '<div id="progressbar" style="display: none;">
                                            <div class="progress-label">
                                                Create/Update...
                                            </div>
                                        </div>',
                ),
            ),
            'submit' => array(
                'title' => $this->l('Create/Update'),
                'name' => 'submitAdd'.$this->table
            )
        );

        $helper = new HelperForm();

        // Module, token and currentIndex
        $helper->module = $this->module;
        $helper->name_controller = $this->module->name;
        $helper->token = $this->token;
        $helper->currentIndex = self::$currentIndex . '&configure=' . $this->module->name;

        // Language
        $helper->default_form_language = $default_lang;
        $helper->allow_employee_form_lang = $default_lang;

        // Title and toolbar
        $helper->title = $this->module->displayName;
        $helper->show_toolbar = true;        // false -> remove toolbar
        $helper->toolbar_scroll = true;      // yes - > Toolbar is always visible on the top of the screen.
        $helper->submit_action = 'submitCreateFriendlyUrls';
        $helper->toolbar_btn = array(
            'save' =>
                array(
                    'desc' => $this->l('Create friendly urls'),
                    'href' => self::$currentIndex . '&configure=' . $this->module->name .
                        '&token=' . $this->token,
                ),
            'back' => array(
                'href' => self::$currentIndex . '&token=' . $this->token,
                'desc' => $this->l('Back to list')
            )
        );

        return $helper->generateForm($fields_form);
    }

    public function ajaxProcessStatusGoogleCategory()
    {
        if (!$id_google_category = (int)Tools::getValue('id_google_category')) {
            die(Tools::jsonEncode(array('success' => false, 'error' => true, 'text' => $this->l('Failed to update the status'))));
        } else {
            $google_category = new GoogleCategory((int)$id_google_category);
            if (Validate::isLoadedObject($google_category)) {
                $google_category->active = $google_category->active == 1 ? 0 : 1;
                $google_category->save() ?
                    die(Tools::jsonEncode(array('success' => true, 'text' => $this->l('The status has been updated successfully')))) :
                    die(Tools::jsonEncode(array('success' => false, 'error' => true, 'text' => $this->l('Failed to update the status'))));
            }
        }
    }

    public function getSubStrText($string)
    {
        return Tools::substr($string, 0, 60);
    }

    /**
     * Return array rows of xls file
     *
     * @param $filename
     * @return array
     */
    private function getXlsFileParams($filename) {

        require_once $this->module->getLocalPath() . 'libs/PHPExcel.php';

        $excel = PHPExcel_IOFactory::load($filename);

        //gets sheets in excel file
        $sheets = $excel->getWorksheetIterator();

        $result = array();
        foreach ($sheets as $sheet) {

            //gets rows in each one sheet
            $rows = $sheet->getRowIterator();

            foreach ($rows as $i => $row) {

                //gets cell in each one row

                $cells = $row->getCellIterator();
                $cells->setIterateOnlyExistingCells(false);

                $result[$i][] = $i;

                foreach ($cells as $cell) {

                    $value = trim($cell->getFormattedValue());

                    if(empty($value))
                        continue;

                    $value = mb_convert_encoding($value, "UTF-8");

                    $result[$i][] = $value;

                }

            }

        }

        return $result;
    }
}