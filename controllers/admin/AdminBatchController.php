<?php
/**
 * Created by PhpStorm.
 * User: setler
 * Date: 10/30/15
 * Time: 10:21 AM
 */

class AdminBatchController extends ModuleAdminController {

    public function ajaxProcessBatch() {

        $response = array('response' => array('stop' => 0));
        $request = Tools::getValue('request');

        if(isset($request['start']) && isset($request['count']) && !empty($request['count']) ) {

            $query = new DbQuery();
            $query->from('google_category_batch')
                ->limit($request['count'], $request['start']);

            $rows = Db::getInstance()->executeS($query);

            if(!empty($rows)) {

                require_once $request['path_to_file'];

                foreach($rows as $row) {
                    $params = explode('::', $row['function']);

                    //include file with method for run
                    $class = $params[0];
                    $method = $params[1];

                    //run function
                    if($class::$method($row['id'], unserialize($row['variables'])))
                        continue;
                }

                $request['start'] = $request['start'] + $request['count'];
                $response['response'] = $request;
            } else {
                $response['response']['stop'] = 1;
            }
        }

        $this->ajaxDie(Tools::jsonEncode($response));
    }

}