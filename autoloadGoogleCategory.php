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
* @package    autoloadGoogleCategory.php
* @author     Dzmitry Urbanovich (urbanovich.mslo@gmail.com)
* @site       http://module-presta.com
* @copyright  Copyright (c) 2007 - 2016 BelVG LLC. (http://www.belvg.com)
* @license    http://store.belvg.com/BelVG-LICENSE-COMMUNITY.txt
*/

define('GO_DIR', _PS_MODULE_DIR_ . 'google_category/');

class autoloadGoogleCategory
{
    public function load($class)
    {
        $files = Tools::scandir(GO_DIR,  'php', '', true);

        foreach($files as $file)
        {
            if(strpos($file, $class) !== false)
                require_once GO_DIR . $file;
        }

    }

    public static function register()
    {
        spl_autoload_register(array(new autoloadGoogleCategory(), 'load'));
    }
}

autoloadGoogleCategory::register();