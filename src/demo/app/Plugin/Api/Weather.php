<?php

namespace Plugin\Api;

class Weather {

    use \Rsf\Traits\Singleton;

    function get($city) {
        $ak = getini('settings/lbsak');
        $url = 'http://api.map.baidu.com/telematics/v3/weather?location=' . urlencode($city) . '&output=json&ak=' . $ak;
        $heads = [
            'Accept' => 'text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8',
            'Accept-Language' => 'zh-cn,zh;q=0.8,en-us;q=0.5,en;q=0.3',
            'Cache-Control' => 'max-age=0',
            'Connection' => 'keep-alive',
            //'Accept-Encoding' => 'gzip',
            'Host' => 'api.map.baidu.com',
            'User-Agent' => 'Mozilla/5.0 (Windows NT 6.1; WOW64; rv:32.0) Gecko/20100101 Firefox/32.0'
        ];
        $res = \Rsf\Helper\Curl::getInstance()->send($url, '', $heads);
        //dump($res,1);
        $ret = ['status' => 0, 'data' => 'nodata'];
        if (200 == $res['http_code']) {
            $json = json_decode($res['body'], true);
            if (isset($json['error']) && $json['error'] == 0) {
                $data = $json['results'][0];
                unset($data['index']);
                $content = $data['currentCity'] . ' PM2.5: ' . $data['pm25'] . " (" . pm25str($data['pm25']) . ")\r\n";
                //dump($data);
                foreach ($data['weather_data'] as $line) {
                    $content .= $line['date'] . $line['weather'] . ' ' . $line['wind'] . ' ' . $line['temperature'];
                    $content .= "\r\n";
                }
                $ret = ['status' => 1, 'data' => $data, 'content' => $content];
            }
        }
        return $ret;
    }

}
