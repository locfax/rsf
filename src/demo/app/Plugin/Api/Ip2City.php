<?php

namespace Plugin\Api;

class Ip2City {

    use \Rsf\Traits\Singleton;

    function get($ip) {
        $ak = getini('settings/lbsak');
        $heads = [
            'Accept' => 'text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8',
            'Accept-Language' => 'zh-cn,zh;q=0.8,en-us;q=0.5,en;q=0.3',
            'Cache-Control' => 'max-age=0',
            'Connection' => 'keep-alive',
            'Accept-Encoding' => 'gzip',
            'Host' => 'api.map.baidu.com',
            'User-Agent' => 'Mozilla/5.0 (Windows NT 6.1; WOW64; rv:32.0) Gecko/20100101 Firefox/32.0'
        ];
        $res = \Rsf\Helper\Curl::getInstance()->send('http://api.map.baidu.com/location/ip?ak=' . $ak . '&ip=' . $ip . '&coor=bd09l', '', $heads);
        $json = json_decode($res['body'], true);
        return $json;
    }

}
