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
* @package    GoogleCategory.php
* @author     Dzmitry Urbanovich (urbanovich.mslo@gmail.com)
* @site       http://module-presta.com
* @copyright  Copyright (c) 2007 - 2016 BelVG LLC. (http://www.belvg.com)
* @license    http://store.belvg.com/BelVG-LICENSE-COMMUNITY.txt
*/

class GoogleCategory extends ObjectModel
{
    public $id_google_category;

    public $name;

    public $id_parent;

    public $id_category;

    public $active;

    public $date_add;

    public $date_upd;

    public static $definition = array(
        'table' => 'google_category',
        'primary' => 'id_google_category',
        'fields' => array(
            'date_add' =>           array('type' => self::TYPE_DATE, 'validate' => 'isDate'),
            'date_upd' =>           array('type' => self::TYPE_DATE, 'validate' => 'isDate'),
            'name' =>               array('type' => self::TYPE_STRING, 'validate' => 'isString', 'size' => 255),
            'id_parent' =>          array('type' => self::TYPE_INT, 'validate' => 'isInt'),
            'id_category' =>        array('type' => self::TYPE_INT, 'validate' => 'isInt'),
            'active' =>             array('type' => self::TYPE_BOOL, 'validate' => 'isBool'),
        ),
    );

    public static function getNestedCategories($root_category = null, $id_lang = false, $active = true, $groups = null,
                                               $use_shop_restriction = true, $sql_filter = '', $sql_sort = '', $sql_limit = '')
    {
        if (isset($root_category) && !Validate::isInt($root_category)) {
            die(Tools::displayError());
        }

        if (!Validate::isBool($active)) {
            die(Tools::displayError());
        }

        if (isset($groups) && Group::isFeatureActive() && !is_array($groups)) {
            $groups = (array)$groups;
        }

        $cache_id = 'GoogleCategory::getNestedCategories_'.md5((int)$root_category.(int)$id_lang.(int)$active.(int)$use_shop_restriction
                .(isset($groups) && Group::isFeatureActive() ? implode('', $groups) : ''));

        if (!Cache::isStored($cache_id)) {

            $result = Db::getInstance()->executeS('
                      SELECT id_category, active, id_parent, name
                      FROM `' . _DB_PREFIX_ . 'google_category`
                        WHERE active = 1
            ');

            array_unshift($result, array(
                'id_category' => 0,
                'id_parent' => 0,
                'name' => 'Root',
            ));

            $categories = array();
            $buff = array();

            if (!isset($root_category)) {
                $root_category = 0;
            }

            foreach ($result as $row) {
                $current = &$buff[$row['id_category']];
                $current = $row;

                if ($row['id_category'] === $root_category) {
                    $categories[$row['id_category']] = &$current;
                } else {
                    $buff[$row['id_parent']]['children'][$row['id_category']] = &$current;
                }
            }

            Cache::store($cache_id, $categories);
        } else {
            $categories = Cache::retrieve($cache_id);
        }

        return $categories;
    }

    public static function getIdCategory($id_google_category)
    {
        return Db::getInstance()->getValue('SELECT id_category FROM `' . _DB_PREFIX_ . self::$definition['table'] . '`
                                                    WHERE id_google_category = ' . pSQL($id_google_category) . '
                                            ');
    }
}