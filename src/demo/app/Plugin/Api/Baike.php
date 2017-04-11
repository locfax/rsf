<?php

namespace Plugin\Api;

class Baike {

    use \Rsf\Traits\Singleton;

    function get($word, $debug = false) {
        $sourl = 'http://baike.baidu.com/search?word=' . urlencode($word) . '&pn=0&rn=0&enc=utf8';
        $heads = [
            'Accept' => 'text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8',
            'Accept-Encoding' => 'gzip',
            'Accept-Language' => 'zh-cn,zh;q=0.8,en-us;q=0.5,en;q=0.3',
            'Cache-Control' => 'max-age=0',
            'Connection' => 'keep-alive',
            //'DNT' => 1,
            'Host' => 'baike.baidu.com',
            'Referer' => $sourl,
            'User-Agent' => 'Mozilla/5.0 (Windows NT 6.1; WOW64; rv:31.0) Gecko/20100101 Firefox/31.0'
        ];
        $mdcurl = \Rsf\Helper\Curl::getInstance();
        $data = $mdcurl->send($sourl, '', $heads, 'gzip', 'UTF-8');
        if ($debug) {
            dump($data['http_info']);
        }
        //dump($data);
        $body = $data['body'];
        preg_match('/<a class="result-title" href="(.*?)" target="_blank">/is', $body, $arr);
        //dump($arr[1], 1);

        $tourl = $arr[1];
        $data = $mdcurl->send($tourl, '', $heads, 'gzip', 'UTF-8');
        if ($debug) {
            dump($data['http_info']);
        }
        //dump($data['header'], 1);
        if ($data['http_code'] == 302) {
            $url = $data['http_info']['redirect_url'];
            $heads['Referer'] = $tourl;
            $heads['Accept-Encoding'] = 'gzip';
            $data = $mdcurl->send($url, '', $heads, 'gzip', 'UTF-8');
            if ($debug) {
                dump($data['http_info']);
            }
        }
        $body = $data['body'];
        if ($debug) {
            dump($body);
        }
        //dump($body);
        preg_match('/<h1 >(.*)<\/h1>/is', $body, $matchs);
        if(!empty($matchs)){
            $subject = $matchs[1];
        }else{
            $subject = '';
        }
        preg_match('/<div class="para" label-module="para">(.*?)<\/div>/is', $body, $matchs);
        if(!empty($matchs)){
            $content = trim(strip_tags($matchs[1]));
        } else {
            $content = '';
        }
        if ($content) {
            $ret = ['status' => 1, 'content'=>$content, 'data' => ['subject' => $subject, 'content' => $content]];
        } else {
            $ret = ['status' => 0, 'content' => 'nodata'];
        }
        return $ret;
    }

}