<?php
/**
 * Created by PhpStorm.
 * User: setler
 * Date: 10/30/15
 * Time: 9:29 AM
 */

abstract class ABatch implements IBatch {
    protected $params;
    abstract public function set($array, $path_to_file);
    abstract public function start();
}