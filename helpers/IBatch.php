<?php
/**
 * Created by PhpStorm.
 * User: setler
 * Date: 10/30/15
 * Time: 9:28 AM
 */

interface IBatch {
    public function set($array, $path_to_file);
    public function start();
}