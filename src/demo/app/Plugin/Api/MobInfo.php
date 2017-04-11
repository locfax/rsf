<?php

namespace Plugin\Api;

class MobInfo {

    use \Rsf\Traits\Singleton;

    function get($phone) {
        $curl = curl_init();
        curl_setopt_array($curl, [
            CURLOPT_URL => "http://a.apix.cn/apixlife/phone/phone?phone=" . $phone,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => "",
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => "GET",
            CURLOPT_HTTPHEADER => [
                "accept: application/json",
                "apix-key: eb1e74dbe3ad4252788d55ba1a736697",
                "content-type: application/json"
            ]
        ]);
        $response = curl_exec($curl);
        $err = curl_error($curl);
        curl_close($curl);
        $curl = null;
        if ($err) {
            return [
                'errcode' => 1,
                'errmsg' => '请求错误!'
            ];
        }
        $res = json_decode($response, true);
        if (0 == $res['error_code']) {
            $errcode = 0;
            if ($res['data']['province'] == $res['data']['city']) {
                $errmsg = $res['data']['province'];
            } else {
                $errmsg = $res['data']['province'] . $res['data']['city'];
            }
        } else {
            $errcode = 1;
            $errmsg = $res['message'];
        }
        return ['errcode' => $errcode, 'errmsg' => $errmsg];
    }
}
