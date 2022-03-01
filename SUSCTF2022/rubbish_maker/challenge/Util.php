<?php

function randomString($length = 8): string
{
    // 防止产生重复string，虽然概率已经很低了？
//    static $record = array();
//    $str = '';
//    for($i=0 ;$i<$length; $i++){
//        $str .= chr(rand(0x80,0xff));
//    }
//    while(array_key_exists($str, $record)){
//        $str = '';
//        for($i=0 ;$i<$length; $i++){
//            $str .= chr(rand(0x80,0xff));
//        }
//    }
//    $record[$str] = 1;
//    return $str;
    $str = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
    $len = strlen($str)-1;
    $randstr = '';
    for ($i=0;$i<$length;$i++) {
        $num=mt_rand(0,$len);
        $randstr .= $str[$num];
    }
    return $randstr;
}

function shuffle_assoc($list) {
    // 数组键值对映射关系保留且打乱顺序
    if (!is_array($list)) return $list;

    $keys = array_keys($list);
    shuffle($keys);
    $random = array();
    foreach ($keys as $key) {
        $random[$key] = $list[$key];
    }
    return $random;
}
