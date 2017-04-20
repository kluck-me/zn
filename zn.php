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

/**
 * HTMLエスケープ。よく使うのでzn_hではなくh。
 * @param mixed $str エスケープしたい値（内部で文字列にキャストされる）
 * @return string HTMLエスケープされた文字列
 */
function h($str) {
  return htmlentities($str, ENT_QUOTES, mb_internal_encoding());
}

/**
 * テンプレート描画
 * @param string $FILE テンプレートのパス
 * @param array $DATA 展開する引数
 * 引数を大文字にすることでテンプレート先でもわかりやすく使えるようにしている。
 */
function zn_render($FILE, $DATA = array()) {
  extract($DATA, EXTR_SKIP);
  require $FILE;
}

/**
 * ファイルをロックしてオープンする
 * @param string $filename ファイルパス
 * @param string $mode アクセス形式
 * @param int [$operation] ロック方法。省略した場合、アクセス形式から推測される。
 * @return resource|null ファイルポインタ
 */
function zn_file_open($filename, $mode, $operation = null) {
  $fp = fopen($filename, $mode);
  if (!$fp) return null;
  if ($operation === null) $operation = ($mode === 'r') ? LOCK_SH : LOCK_EX;
  if (!flock($fp, $operation)) {
    fclose($fp); // 失敗したので閉じる
    return null;
  }
  return $fp;
}

/**
 * ファイルをロックを外してクローズする
 * @param resource $fp ファイルポインタ
 */
function zn_file_close($fp) {
  if ($fp) {
    flock($fp, LOCK_UN); // 失敗してもクローズするので条件には入れない
    fclose($fp);
    $fp = null;
  }
}

/**
 * ファイルを文字列として全部読み込む
 * PHP 5.2.6以前はfile_get_contentsはlockするオプションがないため、このような書き方をする必要がある。
 * @param resource $fp ファイルポインタ
 * @return string 読み込んだ文字列
 * @example
 *   if (($fp = zn_file_open('data.txt'))) {
 *     $data = zn_file_read($fp);
 *     zn_file_close($fp);
 *   }
 */
function zn_file_read($fp) {
  return rewind($fp) ? (string) stream_get_contents($fp) : null; // 存在しないfpの場合は標準のエラーを投げさせる。
}

/**
 * ファイルにデータを全部書き込む（追記ではない）
 * @param resource $fp ファイルポインタ
 * @param string $data 書き込む値
 * @return bool 書き込みに成功したかどうか
 * @example
 *   if (($fp = zn_file_open('data.txt', 'r+'))) {
 *     zn_file_write($fp, 'text');
 *     zn_file_close($fp);
 *   }
 */
function zn_file_write($fp, $data) {
  if ($fp && rewind($fp)) {
    ignore_user_abort(true);
    $result = fwrite($fp, $data);
    if ($result !== false) {
      fflush($fp);
      ftruncate($fp, $result);
      return true;
    }
  }
  return false;
}
