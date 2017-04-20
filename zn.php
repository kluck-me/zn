<?php

/**
 * Zn Core
 * (c) 2017 Kluck
 */

/** 実行開始時刻 * @var double */
if (!defined('ZN_START_TIME')) define('ZN_START_TIME', microtime(true));
/** デバッグモード * @var bool */
if (!defined('ZN_DEBUG')) define('ZN_DEBUG', false);
/** CLIモード * @var bool */
if (!defined('ZN_DEBUG_CLI')) define('ZN_DEBUG_CLI', isset($argc));
/** 内部文字コード * @var string */
if (!defined('ZN_ENCODING')) define('ZN_ENCODING', 'UTF-8');
/** 内部言語設定 * @var string */
if (!defined('ZN_LANGUAGE')) define('ZN_LANGUAGE', 'ja');
/** 内部タイムゾーン設定 * @var string */
if (!defined('ZN_TIMEZONE')) define('ZN_TIMEZONE', 'Asia/Tokyo');

//------------------------------------------------------------------------------

// 内部設定
mb_internal_encoding(ZN_ENCODING);
mb_regex_encoding(ZN_ENCODING);
mb_language(ZN_LANGUAGE);
date_default_timezone_set(ZN_TIMEZONE);

// デバッグ時の設定
if (ZN_DEBUG) {
  // エラー通知の有効化
  error_reporting(E_ALL | E_STRICT);
  ini_set('display_errors', '1');

  // magic quoteが有効ならエラー
  if (get_magic_quotes_gpc()) {
    trigger_error('Not support magic quotes', E_USER_ERROR);
  }

  /**
   * print_r短縮形
   * @param mixed args...
   */
  function pr() {
    $args = func_get_args();
    if (!ZN_DEBUG_CLI) echo '<pre style="white-space:pre-wrap;word-wrap:break-word">';
    foreach ($args as $arg) print_r($arg);
    if (!ZN_DEBUG_CLI) echo '</pre>';
  }

  /**
   * var_dump短縮形
   * @param mixed args...
   */
  function pp() {
    $args = func_get_args();
    if (!ZN_DEBUG_CLI) echo '<pre style="white-space:pre-wrap;word-wrap:break-word">';
    foreach ($args as $arg) var_dump($arg);
    if (!ZN_DEBUG_CLI) echo '</pre>';
  }
}

/**
 * 実行開始からの経過時間を取得
 * @return int 経過したマイクロ秒
 * @example
 * echo zn_elapsed_time(), ' msec.';
 */
function zn_elapsed_time() {
  return (int) ((microtime(true) - ZN_START_TIME) * 1000);
}

/**
 * 配列から値を取得
 * @see https://gist.github.com/nissuk/954926#file-f-php
 * @param mixed &$value 値
 * @param mixed [$default_value=null] 値が存在しないときのデフォルト値
 * @return mixed 取得された値
 * @example
 * $page = zn_array_get($_GET, 'page', 1);
 */
function zn_array_get(&$value, $default_value = null) {
  return isset($value) ? $value : $default_value;
}