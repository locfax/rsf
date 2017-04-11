<?php

namespace Model\Cache;

class Settings {

    use \Rsf\Traits\Singleton;

    public function getdata() {
        $_return = \Rsf\Db::dbm('general')->findAll('common_setting', '`key`,`val`');
        if (empty($_return)) {
            return '';
        }
        $_return = array_index($_return, 'key');
        $return = [];
        foreach ($_return as $key => $line) {
            $return[$key] = $line['val'];
        }
        return $return;
    }

}
