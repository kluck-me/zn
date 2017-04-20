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

// h
green('&lt;img onload=&quot;alert(&#039;xss&#039;)&quot;&gt;', h('<img onload="alert(\'xss\')">'));

// zn_file_*
$fp = zn_file_open(__FILE__, 'r+');
if (green(isset($fp))) {
  green(file_get_contents(__FILE__), zn_file_read($fp));
}
zn_file_close($fp);

$tp = tmpfile();
green(zn_file_write($tp, 'foo'));
green('foo', zn_file_read($tp));
green(zn_file_write($tp, 'bar'));
green('bar', zn_file_read($tp));
fclose($tp);
