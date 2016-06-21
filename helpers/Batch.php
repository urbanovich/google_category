<?php
/**
 * Created by PhpStorm.
 * User: setler
 * Date: 10/30/15
 * Time: 9:28 AM
 */

class Batch extends ABatch {

    protected $params;

    public $success;

    public $context;

    public $start = 0;

    public $count = 10;

    public $path_to_file;

    public function __construct() {

        $this->context = Context::getContext();
    }

    public function set($params, $path_to_file) {

        if(is_array($params)) {

            $data = array();
            foreach($params as $func => $args) {

                if(is_array($args)) {

                    foreach($args as $func => $arg) {

                        $data[] = array(
                            'function' => $func,
                            'variables' => addslashes(serialize($arg))
                        );
                    }

                } else {

                    $data[] = array(
                        'function' => $func,
                        'variables' => addslashes(serialize($args))
                    );
                }
            }

            $data[] = array(
                'function' => 'Batch::cancel',
                'variables' => addslashes(serialize(array('cancel')))
            );

            //if was be errors delete data
            if(Db::getInstance()->getValue('SELECT COUNT(id) FROM ' . _DB_PREFIX_ . 'google_category_batch;') > 0)
                Db::getInstance()->execute('DELETE FROM ' . _DB_PREFIX_ . 'google_category_batch WHERE 1;');

            $this->success = Db::getInstance()->insert('google_category_batch', $data);
            $this->path_to_file = $path_to_file;

            if(!$this->success) {

                throw new Exception('Batch: error in time insert to data table ' . _DB_PREFIX_ . 'google_category_batch.');
            }

        } else {

            throw new Exception('Batch: Array $params must be array, not array given.');
        }

        return $this;
    }

    public function start() {

//        if($this->success
//            && $rows = Db::getInstance()->getValue('SELECT COUNT(id) FROM ' . _DB_PREFIX_ . 'google_category_batch;')) {

            $count = (10 < $this->count) ? 10 : $this->count;
            Media::addJsDef(array(
                'url' => $this->context->link->getAdminLink('AdminBatch') . '&action=batch&ajax=1',
                'start' => $this->start,
                'count' => $count,
                'percent' => 10,
                'path_to_file' => $this->path_to_file,
            ));
//        }
    }

    public static function cancel(){

        //if was be errors delete data
        if(Db::getInstance()->getValue('SELECT COUNT(id) FROM ' . _DB_PREFIX_ . 'google_category_batch;') > 0)
            Db::getInstance()->execute('DELETE FROM ' . _DB_PREFIX_ . 'google_category_batch WHERE 1;');
    }
}