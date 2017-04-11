<?php

namespace Restful;

class Test extends \Rsf\Controller {

    function act_index() {
        ob_start();
        echo '<pre>';
        phpinfo();
        $data = ob_get_contents();
        ob_end_clean();
        $this->rephtml($data);
    }

}