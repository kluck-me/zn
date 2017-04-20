<?php

define('ZN_DEBUG', true);
require_once 'zn.php';

require_once 'green.php';
foreach (glob('tests/*.php') as $filepath) {
  require_once $filepath; // 特にテスト用かは判定していない（する方法がない）
}
