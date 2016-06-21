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
* @package    GoogleCategoryProduct.php
* @author     Dzmitry Urbanovich (urbanovich.mslo@gmail.com)
* @site       http://module-presta.com
* @copyright  Copyright (c) 2007 - 2016 BelVG LLC. (http://www.belvg.com)
* @license    http://store.belvg.com/BelVG-LICENSE-COMMUNITY.txt
*/

class GoogleCategoryProduct extends ObjectModel
{

    public $id_google_category_product;

    public $id_google_category;

    public $id_product;

    public $date_add;

    public $date_upd;

    public static $definition = array(
        'table' => 'google_category_product',
        'primary' => 'id_google_category_product',
        'multilang' => true,
        'multilang_shop' => true,
        'fields' => array(
            'date_add' =>           array('type' => self::TYPE_DATE, 'validate' => 'isDate'),
            'date_upd' =>           array('type' => self::TYPE_DATE, 'validate' => 'isDate'),
            'id_google_category' => array('type' => self::TYPE_INT, 'lang' => true, 'validate' => 'isInt'),
            'id_product' =>         array('type' => self::TYPE_INT, 'lang' => true,'validate' => 'isInt'),
        )
    );

    public function __construct($id = null, $id_lang = null, $id_shop = null)
    {
        Shop::addTableAssociation(self::$definition['table'], array('type' => 'shop'));
        parent::__construct($id, $id_lang, $id_shop);
    }

    public static function getProductById($id_product)
    {
        $context = Context::getContext();
        return Db::getInstance()->executeS('SELECT * FROM `' . _DB_PREFIX_ . self::$definition['table'] . '` AS gcp
                                                    LEFT JOIN `' . _DB_PREFIX_ . self::$definition['table'] . '_lang` AS gcpl ON (
                                                        gcpl.id_google_category_product = gcp.id_google_category_product AND
                                                        gcpl.id_shop = ' . $context->shop->id . ' AND
                                                        gcpl.id_lang = ' . $context->language->id . '
                                                    )
                                                    WHERE gcpl.id_product = ' . pSQL($id_product));
    }

    public static function deleteProducts($id_product)
    {
        $products = self::getProductById($id_product);
        foreach($products as $product)
        {
            $google_category_product = new GoogleCategoryProduct($product['id_google_category_product']);
            $google_category_product->delete();
        }
    }

}