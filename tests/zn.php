<?php

// zn_elapsed_time
green('integer', gettype(zn_elapsed_time()));
green(0, '<', zn_elapsed_time());

// zn_array_get
$arr = array( 'foo' => 'abc' );
// - exists key
green(isset($arr['foo'])) && green('abc', $arr['foo']);
green('abc', zn_array_get($arr['foo']));
green('abc', zn_array_get($arr['foo'], 'iroha'));
green(isset($arr['foo'])) && green('abc', $arr['foo']);
// - non-exists key
green(!isset($arr['hoge']));
green(null, zn_array_get($arr['hoge']));
green('iroha', zn_array_get($arr['hoge'], 'iroha'));
green(!isset($arr['hoge']));
unset($arr);
