<?php

/**
 * テストライブラリ Green
 * (c) 2017 Kluck
 */

class Green {
  /**
   * シングルトンインスタンス
   * @var Green
   */
  private static $instance;

  /**
   * シングルトンメソッド
   * @return Green
   */
  public static function getInstance() {
    if (!self::$instance) self::$instance = new self;
    return self::$instance;
  }

  /**
   * 賢い比較
   * @param mixed $a
   * @param mixed $b
   * @return bool
   */
  private static function smart_equals($a, $b) {
    $type = gettype($a);
    if ($type !== gettype($b)) return false; // 型が違うならfalse
    switch ($type) {
      case 'double':
        if (is_nan($a)) return is_nan($b); // NaN同士ならtrue
        break;
      case 'array':
        if (count($a) !== count($b)) return false; // 件数が違うならfalse
        foreach ($a as $k => $v) {
          if (!isset($b[$k]) || !self::smart_equals($v, $b[$k])) return false; // 一つでも内容が違うならfalse
        }
        // 件数が同じなので、$bのキーを列挙する必要はない
        return true;
      case 'object':
      case 'resource':
        return var_export($a, true) === var_export($b, true); // 方法がないので、文字列比較する
    }
    return $a === $b; // 厳密比較
  }

  /**
   * 賢いキーバリュー探索
   * @param string $target=key|value 探索対象
   * @param mixed $a
   * @param mixed $b
   * @return bool
   */
  private function smart_exists($target, $a, $b) {
    $key = ($target === 'key');
    foreach ($a as $k => $v) {
      if (self::smart_equals($key ? $k : $v, $b)) {
        return true;
      }
    }
    return false;
  }

  /**
   * テスト結果
   * @var array
   * @see self::__construct
   */
  private $results;

  /**
   * 描画エンジン
   * @var GreenRenderer
   */
  private $renderer;

  /**
   * コンストラクタ。シングルトン仕様
   */
  private function __construct() {
    $this->results = array(
      'tests' => array(),
      'results' => array(),
      'success' => 0,
      'failure' => 0,
    );
  }

  /**
   * デストラクタ。呼び出し時に結果を出力
   */
  public function __destruct() {
    if ($this->renderer) {
      $this->renderer->render($this->results);
    }
  }

  /**
   * 描画エンジンの設定
   * @param GreenRenderer $renderer 描画エンジン
   */
  public function setRenderer($renderer) {
    $this->renderer = $renderer;
  }

  /**
   * アサーション
   * @param mixed [$expected=true]
   * @param string [$operator='=']
   * @param mixed $actual
   */
  public function assert() {
    $args = func_get_args();

    // 二引数の場合は$expectedと$actualが、一引数の場合は$actualのみが指定されたとみなす
    switch (count($args)) {
      case 3: break;
      case 2: $args = array($args[0], '=', $args[1]); break;
      case 1: $args = array(true, '=', $args[0]); break;
      default:
        throw new Exception('invalid arguments: args = ' . var_export($args, true));
    }
    list($expected, $operator, $actual) = $args;

    // 演算子文字列ごとに評価する
    switch ($operator) {
      case '===':
      case '==':
      case '=':  $result = self::smart_equals($expected, $actual); break;
      case '<>':
      case '!==':
      case '!=':
      case '!':  $result = !self::smart_equals($expected, $actual); break;
      case '<':  $result = ($expected < $actual); break;
      case '<=': $result = ($expected <= $actual); break;
      case '>':  $result = ($expected > $actual); break;
      case '>=': $result = ($expected >= $actual); break;
      case '=~': $result = (preg_match($expected, $actual) === 1); break;
      case '!~': $result = (preg_match($expected, $actual) === 0); break;
      case 'include':   $result = self::smart_exists('key', $expected, $actual); break;
      case 'exclude':   $result = !self::smart_exists('key', $expected, $actual); break;
      case 'any':
      case 'have_any':  $result = self::smart_exists('value', $expected, $actual); break;
      case 'none':
      case 'have_none': $result = !self::smart_exists('value', $expected, $actual); break;
      default:
        throw new Exception('invalid operator: operator = ' . var_export($operator, true));
    }

    // 結果の格納
    $test = array(
      'result' => $result,
      'args' => $args,
    );

    // このファイルではない初めての呼び出しをテストの実行箇所とみなす
    foreach (debug_backtrace() as $trace) {
      if (isset($trace['file']) && $trace['file'] !== __FILE__) {
        $test['backtrace'] = $trace;
        break;
      }
    }

    $this->results['tests'][] = $test;
    $this->results['results'][] = $result;
    $this->results[$result ? 'success' : 'failure']++;
    return $result;
  }
}

/**
 * GreenRenderer
 */
abstract class GreenRenderer {
  /**
   * 失敗事例の抽出
   * @param array $results Greenの結果
   * @return array
   */
  private static function extractFailures($results) {
    $failures = array();
    $files = array();

    foreach ($results['tests'] as $i => $test) {
      if ($test['result']) continue; // 成功時はスキップ

      $file = isset($test['backtrace']['file']) ? $test['backtrace']['file'] : null;
      $line = isset($test['backtrace']['line']) ? $test['backtrace']['line'] : 0;

      // $files[$file] に行単位のファイルをキャッシュ
      if (!isset($files[$file]) && file_exists($file)) $files[$file] = file($file);

      $failures[] = array(
        'number' => $i + 1,
        'code' => $file && isset($files[$file][$line - 1]) ? trim($files[$file][$line - 1]) : null,
        'file' => $file,
        'line' => $line,
        'expected' => $test['args'][0],
        'operator' => $test['args'][1],
        'actual' => $test['args'][2],
      );
    }

    return $failures;
  }

  /**
   * 文字列の装飾
   * @param mixed $style 装飾データ
   * @param string $str 文字列
   * @return string 装飾文字列
   */
  abstract protected function styled($style, $str);

  /**
   * ヘッダーの描画
   * 出力しないケースもあるので、abstractではない。
   */
  protected function renderHeader() {}

  /**
   * フッターの描画
   * 出力しないケースもあるので、abstractではない。
   */
  protected function renderFooter() {}

  /**
   * 表題の描画
   * @param string $caption 表題
   */
  abstract protected function renderCaption($caption);

  /**
   * 結果の描画
   * @param bool[] $results 結果
   */
  abstract protected function renderResults($results);

  /**
   * 失敗結果の描画
   * @param array $failures 失敗結果
   */
  abstract protected function renderFailures($failures);

  /**
   * 出力結果の格納
   * @var array
   */
  protected $results;

  /**
   * 描画
   * @param array $results Greenの結果
   */
  public function render($results) {
    $this->results = $results;
    $failures = self::extractFailures($results); // 失敗ケースの取得
    $this->renderHeader();
    $this->renderCaption(($results['success'] + $results['failure']) . ' tests');
    $this->renderResults($results['results']);
    if (count($failures)) $this->renderFailures($failures); // 失敗ケースがないときは描画しない
    $this->renderFooter();
  }

  /**
   * スタイル指定をした表題の描画
   * @param string $caption 表題
   * @param string $successStyle 成功時スタイル
   * @param string $failureStyle 失敗時スタイル
   */
  protected function printCaptionWithStyle($caption, $successStyle, $failureStyle) {
    echo $this->styled($this->results['failure'] ? $failureStyle : $successStyle, $caption);
  }

  /**
   * スタイル指定をした結果の描画
   * @param array $results 結果
   * @param string $successStyle 成功時スタイル
   * @param string $failureStyle 失敗時スタイル
   */
  protected function printResultsWithStyle($results, $successStyle = null, $failureStyle = null) {
    foreach ($results as $r) {
      echo $r ? $this->styled($successStyle, '.') : $this->styled($failureStyle, 'F');
    }
  }

  /**
   * 失敗結果の描画
   * @param array $failures 失敗結果
   * @param string [$header=null] 結果ごとのヘッダー
   * @param string [$footer=null] 結果ごとのフッター
   */
  protected function printFailuresWithHeaderAndFooter($failures, $header = null, $footer = null) {
    // 連番の長さのインデントの生成
    //   123 -> '   ' (3つのスペース)
    $indent = preg_replace('/./', ' ', '' . $failures[count($failures) - 1]['number']);
    // フォーマットは以下を生成（カスタマイズ難しい）
    // 9) green('foo', 1);
    //    'foo' (string) = 1 (integer)
    //    /git/zn/tests/green.php: 4
    $format = PHP_EOL . '%' . count($indent) . 'd) %s'
            . PHP_EOL . $indent . '  %s (%s) %s %s (%s)'
            . PHP_EOL . $indent . '  %s: %d'
            . PHP_EOL;
    foreach ($failures as $failure) {
      if ($header) echo $header;
      printf(
        $format,
        $failure['number'], $failure['code'],
        var_export($failure['expected'], true), gettype($failure['expected']), $failure['operator'], var_export($failure['actual'], true), gettype($failure['actual']),
        $failure['file'], $failure['line']
      );
      if ($footer) echo $footer;
    }
  }
}

/**
 * GreenCLIRenderer
 */
class GreenCLIRenderer extends GreenRenderer {
  /** @see GreenRender#styled */
  protected function styled($style, $str) {
    return "\033[{$style}m{$str}\033[0m";
  }

  /** @see GreenRender#renderCaption */
  protected function renderCaption($caption) {
    $this->printCaptionWithStyle($caption, 42, 41);
    echo ':', PHP_EOL;
  }

  /** @see GreenRender#renderResults */
  protected function renderResults($results) {
    $this->printResultsWithStyle($results, 32, 31);
    echo PHP_EOL;
  }

  /** @see GreenRender#renderFailures */
  protected function renderFailures($failures) {
    $this->printFailuresWithHeaderAndFooter($failures);
    echo PHP_EOL;
  }
}

/**
 * GreenHTMLRenderer
 */
class GreenHTMLRenderer extends GreenRenderer {
  /** @see GreenRender#styled */
  protected function styled($style, $str) {
    return '<span style="' . htmlspecialchars($style, ENT_QUOTES) . '">' . htmlspecialchars($str, ENT_QUOTES) . '</span>';
  }

  /** @see GreenRender#renderHeader */
  protected function renderHeader() {
    echo '<!DOCTYPE html><html><head><title>Tests</title><style>body{font-family:monospace}p{word-wrap:break-word;white-space:pre-wrap}</style></head><body>';
  }

  /** @see GreenRender#renderFooter */
  protected function renderFooter() {
    echo '</body></html>';
  }

  /** @see GreenRender#renderCaption */
  protected function renderCaption($caption) {
    $t = 'color:white;background-color:';
    echo '<h1>';
    $this->printCaptionWithStyle($caption, $t . 'green', $t . 'red');
    echo '</h1>';
  }

  /** @see GreenRender#renderResults */
  protected function renderResults($results) {
    echo '<p>';
    $this->printResultsWithStyle($results, 'color:green', 'color:red');
    echo '</p>';
  }

  /** @see GreenRender#renderFailures */
  protected function renderFailures($failures) {
    $this->printFailuresWithHeaderAndFooter($failures, '<p>', '</p>');
  }
}

// 標準描画エンジンを設定
//   $argcの有無でCLIかどうかが判定できる
Green::getInstance()->setRenderer(isset($argc) ? (new GreenCLIRenderer) : (new GreenHTMLRenderer));

/**
 * Green::getInstance()->assertの短縮形
 * @see Green#assert
 */
function green() {
  return call_user_func_array(array(Green::getInstance(), 'assert'), func_get_args());
}

/**
 * 例外が発生するか検査
 * @param string|string[] $exception_names 例外名
 * @param function $callback
 */
function green_error($exception_names, $callback) {
  if (!is_array($exception_names)) {
    $exception_names = array($exception_names);
  }
  $error_class_name = null;
  try {
    $callback();
  } catch (Exception $e) {
    $error_class_name = get_class($e);
  }
  return green($exception_names, 'any', $error_class_name);
}
