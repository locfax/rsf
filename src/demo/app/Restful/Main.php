<?php

namespace Restful;

class Main extends \Rsf\Controller {

    function act_index() {
        $tid = $this->request->get('tid');
        $tid = $tid ? intval($tid) : 44;
        $rows = [$tid];
        //$row = \Rsf\Db::findOne('node', 'tid,fname,subject,tags', ['tid'=>$tid]);

        //$row = ['main', 'index', $this->request->getClientIP(), date('Y-m-d H:i:s')];
        //$this->repjson($row);
        include template('main');
        return $this->render();
    }
}