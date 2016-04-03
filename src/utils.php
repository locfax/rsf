<?php

//辅助函数

function dimplode($arr) {
    return "'" . implode("','", (array)$arr) . "'";
}

/*
 *
 * 屏蔽单双引号等
 * 提供给数据库搜索
 */
function input_char($text) {
    if (empty($text)) {
        return $text;
    }
    return htmlspecialchars(trim($text), ENT_QUOTES, 'UTF-8');
}

/*
*  屏蔽单双引号等
*  提供给html显示 或者 input输入框
*/
function input_text($text) {
    if (empty($text)) {
        return $text;
    }
    return htmlspecialchars(stripslashes($text), ENT_QUOTES, 'UTF-8');
}

/*
 *
 * function input_char 的还原
 */
function output_char($text) {
    if (empty($text)) {
        return $text;
    }
    return stripslashes(htmlspecialchars_decode($text, ENT_QUOTES));
}

/* qutotes get post cookie by \'
 * return string
 */
function daddslashes($string) {
    if (empty($string)) {
        return $string;
    }
    if (is_numeric($string)) {
        return $string;
    }
    if (is_array($string)) {
        return array_map('daddslashes', $string);
    }
    return addslashes($string);
}

/*
 * it's paire to daddslashes
 */
function dstripslashes($value) {
    if (empty($value)) {
        return $value;
    }
    if (is_numeric($value)) {
        return $value;
    }
    if (is_array($value)) {
        return array_map('dstripslashes', $value);
    }
    return stripslashes($value);
}

function floatvaldec($v, $dec = ',') {
    return floatval(str_replace(",", ".", preg_replace("[^-0-9$dec]", "", $v)));
}

function strexists($str, $needle) {
    return !(false === strpos($str, $needle));
}

function array_index($arr, $col) {
    if (!is_array($arr)) {
        return $arr;
    }
    $rows = array();
    foreach ($arr as $row) {
        $rows[$row[$col]] = $row;
    }
    return $rows;
}

/**
 * @param $arr
 * @param string $delval
 * @return array
 */
function array_remove_value($arr, $delval = '') {
    if (empty($arr)) {
        return null;
    }
    foreach ($arr as $key => $value) {
        if (is_array($value)) {
            $arr[$key] = array_remove_value($value);
        } else {
            if ($delval === $value) {
                unset($arr[$key]);
            } else {
                $arr[$key] = $value;
            }
        }
    }
    return $arr;
}

/**
 * @param $utimeoffset
 * @return array
 */
function loctime($utimeoffset) {
    static $dtformat = null, $timeoffset = 8;
    if (is_null($dtformat)) {
        $dtformat = array(
            'd' => getini('settings/dateformat') ?: 'Y-m-d',
            't' => getini('settings/timeformat') ?: 'H:i:s'
        );
        $dtformat['dt'] = $dtformat['d'] . ' ' . $dtformat['t'];
        $timeoffset = getini('settings/timezone') ?: $timeoffset; //defualt is Asia/Shanghai
    }
    $offset = $utimeoffset == 999 ? $timeoffset : $utimeoffset;
    return [$offset, $dtformat];
}

/**
 * @param $timestamp
 * @param string $format
 * @param int $utimeoffset
 * @param string $uformat
 * @return string
 */
function dgmdate($timestamp, $format = 'dt', $utimeoffset = 999, $uformat = '') {
    if (!$timestamp) {
        return '';
    }
    $loctime = loctime($utimeoffset);
    $offset = $loctime[0];
    $dtformat = $loctime[1];
    $timestamp += $offset * 3600;
    if ('u' == $format) {
        $nowtime = time() + $offset * 3600;
        $todaytimestamp = $nowtime - $nowtime % 86400;
        $format = !$uformat ? $dtformat['dt'] : $uformat;
        $s = gmdate($format, $timestamp);
        $time = $nowtime - $timestamp;
        if ($timestamp >= $todaytimestamp) {
            if ($time > 3600) {
                return '<span title="' . $s . '">' . intval($time / 3600) . '&nbsp;小时前</span>';
            } elseif ($time > 1800) {
                return '<span title="' . $s . '">半小时前</span>';
            } elseif ($time > 60) {
                return '<span title="' . $s . '">' . intval($time / 60) . '&nbsp;分钟前</span>';
            } elseif ($time > 0) {
                return '<span title="' . $s . '">' . $time . '&nbsp;秒前</span>';
            } elseif (0 == $time) {
                return '<span title="' . $s . '">刚才</span>';
            } else {
                return $s;
            }
        } elseif (($days = intval(($todaytimestamp - $timestamp) / 86400)) >= 0 && $days < 7) {
            if (0 == $days) {
                return '<span title="' . $s . '">昨天&nbsp;' . gmdate('H:i', $timestamp) . '</span>';
            } elseif (1 == $days) {
                return '<span title="' . $s . '">前天&nbsp;' . gmdate('H:i', $timestamp) . '</span>';
            } else {
                return '<span title="' . $s . '">' . ($days + 1) . '&nbsp;天前</span>';
            }
        } elseif (gmdate('Y', $timestamp) == gmdate('Y', $nowtime)) {
            return '<span title="' . $s . '">' . gmdate('m-d H:i', $timestamp) . '</span>';
        } else {
            return $s;
        }
    }
    $format = isset($dtformat[$format]) ? $dtformat[$format] : $format;
    return gmdate($format, $timestamp);
}

function durlencode($value) {
    if (is_array($value)) {
        return array_map('durlencode', $value);
    }
    return $value ? urlencode($value) : $value;
}

/*
 * json encode
 */
function output_json($arr) {
    if (empty($arr)) {
        return '[]';
    }
    if (floatvaldec(PHP_VERSION) >= 5.4) {
        return json_encode($arr, JSON_UNESCAPED_UNICODE);
    }
    $json = json_encode(durlencode($arr));
    return urldecode($json);
}