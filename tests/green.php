<?php

green(true);
green(true, '!', false);
green(null, null);
green(acos(8), acos(8)); // acos(8) == NaN
green(acos(8), '!', acos(1));
green(log(0), log(0)); // log(0) == INF
green(log(0), '!', log(1));
green(array(1, '2', 'k2' => 'v2', 'k' => 'v'), array(1, '2', 'k' => 'v', 'k2' => 'v2'));
green(array(1, '2', 'k2' => 'v2', 'k' => 'v'), '!', array(1, '2', 'k' => 'v', 'k2' => 'v3'));
$a_obj = new stdClass;
$a_obj->k = 'v';
$b_obj = new stdClass;
$b_obj->k = 'v';
green($a_obj, $b_obj);
class T {
  private $v1;
  public $v2;
  public function __construct($v1, $v2) {
    $this->v1 = $v1;
    $this->v2 = $v2;
  }
}
green(new T(1, 2), new T(1, 2));
green(1, '<', 2);
green('z', '>', 'a');
green('/^hoge/', '=~', 'hogefuga');
green('/^hage/', '!~', 'hogefuga');
green(array('k' => 1), 'include', 'k');
green(array('k' => 1), 'exclude', 'v');
green(array('k'), 'any', 'k');
green(array('k'), 'none', 'v');
class A_Exception extends Exception {}
green_error('A_Exception', function () { throw new A_Exception; });
green_error(array('A_Exception', 'B_Exception'), function () { throw new A_Exception; });
