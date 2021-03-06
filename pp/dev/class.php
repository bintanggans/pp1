<?php

if (!class_exists('Console')) {
  include_once __DIR__ . '/console.php';
}
class PP
{
  public static $sleep = 5;
  public static $wrap_config = [];
  public static $ua = 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/79.0.3945.130 Safari/537.36';
  public static $pp = 'https://www.paypal.com/';
  public static $v1 = '/myaccount/money/api/currencies/transfer';
  public static $v2 = '/myaccount/money/api/currencies/exchange-rate';
  public static $amount;
  public static $from;
  public static $to;
  private static $_instance = null;
  public static $cookie;
  public static $csrf;

  public static function init()
  {
    if (null === self::$_instance) {
      self::$_instance = new self();
    }

    return self::$_instance;
  }

  /**
   * Build PP.
   *
   * @param string $path
   */
  public static function build($path)
  {
    return preg_replace('/\/{2,9}/', '/', self::$pp . '/' . $path);
  }

  /**
   * Load PP.
   *
   * @param string $url
   * @param array  $h
   * @param string $body
   */
  public static function cload($url, $h, $body)
  {
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_HTTPHEADER, $h);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $body);
    curl_setopt($ch, CURLOPT_POST, 1);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    $x = curl_exec($ch);
    curl_close($ch);

    return $x;
  }

  /**
   * Is amount valid.
   *
   * @param number|null $n
   */
  public function check_amount($n = null)
  {
    if (!$n) {
      $n = self::$amount;
    }

    return $n && is_numeric($n) && 0 != $n;
  }

  /**
   * Set User-agent.
   *
   * @param string $ua
   */
  public function set_ua($ua)
  {
    if (is_string($ua)) {
      self::$ua = $ua;
    }
  }

  public function set_amount($n)
  {
    if (strpos($n, ':')) {
      $e = explode(':', $n);
      $n = $e[0];
    }
    switch ($n) {
      case 'jpy_to_twd':
        $n = 2;
        break;
      case 'twd_to_usd':
        $n = 3;
        break;
      case 'usd_to_twd':
        $n = 0.02;
        break;
    }
    if (self::check_amount($n)) {
      self::$amount = $n;
    }

    return $n;
  }

  /**
   * Remove amount.
   */
  public function remove_amount()
  {
    self::$amount = null;
  }

  public function get_amount()
  {
    return self::$amount;
  }

  /**
   * Console result output.
   *
   * @param string           $fn     function name
   * @param string           $output
   * @param string|int|float $amount
   */
  public static function console($fn, $output, $amount)
  {
    if (!self::$to) {
      exit('To Currency null');
    }
    if (!self::$from) {
      exit('From Currency null');
    }

    $result = Console::red(date('d-m-Y H:i:s ') . ' Gagal Convert ' . self::get_amount() . ' ' . self::$from . " to $amount " . self::$to . ' (' . $fn . ')');
    if (true == strpos($output, 'null')) {
      $result = Console::green(date('d-m-Y H:i:s ') . ' Berhasil convert ' . self::get_amount() . ' ' . self::$from . " to $amount " . self::$to . ' (' . $fn . ')');
    }
    echo $result . "\n";
  }

  /**
   * Body builder.
   *
   * @param string $from
   * @param string $to
   * @param string $csrf
   */
  public static function body($from, $to)
  {
    self::$from = $from;
    self::$to = $to;
    if (0 == self::get_amount()) {
      exit(__FUNCTION__ . ' amount (' . self::get_amount() . ') is zero');
    }
    $result = "{\"sourceCurrency\":\"$from\",\"sourceAmount\":" . self::get_amount() . ",\"targetCurrency\":\"$to\",\"_csrf\":\"" . self::$csrf . '"}';
    //var_dump($result);
    return $result;
  }

  /**
   * Build header.
   *
   * @param string $cookie
   *
   * @return explode
   */
  public static function header()
  {
    $arr = ["\r", '	'];

    return explode("\n", str_replace($arr, '', 'Cookie: ' . self::$cookie . '
Content-Type: application/json
user-agent: ' . self::$ua));
  }

  /**
   * USD to TWD.
   *
   * @param string $cookie
   * @param string $csrf
   *
   * @return json_decode
   */
  public static function usd_to_twd()
  {
    if (!self::check_amount()) {
      self::set_amount(__FUNCTION__);
    }

    $url = 'https://www.paypal.com/myaccount/money/api/currencies/transfer';
    $h = self::header();
    $body = self::body('USD', 'TWD');

    if (self::dev()) {
      self::log(__FUNCTION__, self::$amount, self::$from, self::$to);
    }

    return json_decode(self::cload($url, $h, $body), true);
  }

  public static function usd_to_jpy()
  {
    if (!self::check_amount()) {
      self::set_amount(__FUNCTION__);
    }

    $url = 'https://www.paypal.com/myaccount/money/api/currencies/transfer';
    $h = self::header();
    $body = self::body('USD', 'JPY');

    if (self::dev()) {
      self::log(__FUNCTION__, self::$amount, self::$from, self::$to);
    }

    return json_decode(self::cload($url, $h, $body), true);
  }

  public static function usd_to_ils()
  {
    if (!self::check_amount()) {
      self::set_amount(__FUNCTION__);
    }

    $url = 'https://www.paypal.com/myaccount/money/api/currencies/transfer';
    $h = self::header();
    $body = self::body('USD', 'ILS');

    if (self::dev()) {
      self::log(__FUNCTION__, self::$amount, self::$from, self::$to);
    }

    return json_decode(self::cload($url, $h, $body), true);
  }

  /**
   * TWD to USD.
   *
   * @param string $cookie
   * @param string $csrf
   *
   * @return json_decode
   */
  public static function twd_to_usd()
  {
    if (!self::check_amount()) {
      self::set_amount(__FUNCTION__);
    }

    $url = 'https://www.paypal.com/myaccount/money/api/currencies/transfer';
    $h = self::header();
    $body = self::body('TWD', 'USD');

    if (self::dev()) {
      self::log(__FUNCTION__, self::$amount, self::$from, self::$to);
    }

    return json_decode(self::cload($url, $h, $body), true);
  }

  /**
   * JPY to USD.
   *
   * @param string $cookie
   * @param string $csrf
   *
   * @return json_decode
   */
  public static function jpy_to_twd()
  {
    if (!self::check_amount()) {
      self::set_amount(__FUNCTION__);
    }

    $url = 'https://www.paypal.com/myaccount/money/api/currencies/transfer';
    $h = self::header();
    $body = self::body('JPY', 'TWD');

    if (self::dev()) {
      self::log(__FUNCTION__, self::$amount, self::$from, self::$to);
    }

    return json_decode(self::cload($url, $h, $body), true);
  }

  /**
   * TWD to USD Executor.
   *
   * @param string $cookie
   * @param string $csrf
   *
   * @return json_decode
   */
  public static function twd2usd()
  {
    if (self::dev()) {
      self::log(__FUNCTION__, self::$amount, self::$from, self::$to);
    }
    $twd_to_usd = self::twd_to_usd();
    $output = json_encode($twd_to_usd);
    $amount = getStr($output, '"value":"', '"');
    self::console(__FUNCTION__, $output, $amount);
    self::sleep();
  }

  public static function usd2jpy()
  {
    if (self::dev()) {
      self::log(__FUNCTION__, self::$amount, self::$from, self::$to);
    }
    $run = self::usd_to_jpy();
    $output = json_encode($run);
    $amount = getStr($output, '"value":"', '"');
    self::console(__FUNCTION__, $output, $amount);
    self::sleep();
  }

  /**
   * USD to TWD Executor.
   *
   * @param string $cookie
   * @param string $csrf
   *
   * @return json_decode
   */
  public static function usd2twd()
  {
    if (self::dev()) {
      self::log(__FUNCTION__, self::$amount, self::$from, self::$to);
    }
    $usd_to_twd = self::usd_to_twd();
    $output = json_encode($usd_to_twd);
    $amount = getStr($output, '"value":"', '"');
    self::console(__FUNCTION__, $output, $amount);
    self::sleep();
  }

  public static function log(...$fn)
  {
    echo Console::yellow(implode(', ', $fn) . "\n");
  }

  public static function dev()
  {
    return 'L3n4r0x-PC' == gethostname();
  }

  /**
   * JPY to USD Executor.
   *
   * @param string $cookie
   * @param string $csrf
   *
   * @return json_decode
   */
  public static function jpy2twd()
  {
    if (self::dev()) {
      self::log(__FUNCTION__, self::$amount, self::$from, self::$to);
    }
    $jpy_to_twd = self::jpy_to_twd();
    $output = json_encode($jpy_to_twd);
    $amount = getStr($output, '"value":"', '"');
    self::console(__FUNCTION__, $output, $amount);
    self::sleep();
  }

  public static function set_sleep($n)
  {
    if (is_numeric($n) && $n > 0) {
      self::$sleep = $n;
    }
  }

  public static function sleep()
  {
    echo 'Delay ' . self::$sleep . " Secs\n";

    return sleep(self::$sleep);
  }

  public function verify($rumus, $callback = null)
  {
    if (is_iterable($rumus)) {
      foreach ($rumus as $e) {
        $e = trim($e);
        $f = $e;
        $sleep = 1;
        $amount = 0;
        if (strpos($e, ':')) {
          $ex = explode(':', $e);
          /*
           * @see https://regex101.com/r/avhwZg/2/
           */
          foreach ($ex as $transversible) {
            if (method_exists(__CLASS__, $transversible)) {
              $f = $transversible;
              $amount = self::set_amount($transversible);
            }
            if (preg_match('/sleep\((\d\.?\d*)\)/m', $transversible, $sl)) {
              if (!isset($sl[1]) || !is_numeric($sl[1])) {
                throw new Exception($sl[1] . ' is not number OR Invalid on rumus ' . $sl[0]);
              }
              if (is_numeric($sl[1]) && 0 != $sl[1]) {
                $sleep = $sl[1];
              }
            }
            if (preg_match('/amount\((\d\.?\d*)\)/m', $transversible, $sl)) {
              if (!isset($sl[1]) || !is_numeric($sl[1])) {
                throw new Exception($sl[1] . ' is not number OR Invalid on rumus ' . $sl[0]);
              }
              if (is_numeric($sl[1]) && 0 != $sl[1]) {
                $amount = $sl[1];
              }
            }
          }
        }

        if (!method_exists(__CLASS__, $f)) {
          throw new Exception("$f is not function");
        } else {
          self::$wrap_config[] = [
            'function' => $f,
            'sleep' => $sleep,
            'amount' => $amount,
            'rumus' => $e,
          ];
        }
      }
      //self::dump(self::$wrap_config);
      if (is_callable($callback)) {
        foreach (self::$wrap_config as $function) {
          if (0 == $function['amount']) {
            $function['amount'] = self::set_amount(self::shift_function($function['rumus']));
          }
          //self::dump($function);
          call_user_func($callback, $function['rumus'], $function['function'], $function['amount'], $function['sleep'], count(self::$wrap_config));
        }
        self::$wrap_config = [];
      }
    }
  }

  public static function dump(...$c)
  {
    exit(var_dump($c));
  }

  public static function shift_function($f)
  {
    return str_replace('2', '_to_', $f);
  }

  /**
   * Set Cookie.
   *
   * @param string $str
   *
   * @return void
   */
  public static function setCookie($str)
  {
    self::$cookie = trim($str);
  }

  /**
   * Set CSRF.
   *
   * @param string $str
   *
   * @return void
   */
  public static function setCsrf($str)
  {
    if (self::isJson($str)) {
      $j = (array) json_decode($str);
      $str = isset($j['_csrf']) ? $j['_csrf'] : die('CSRF invalid JSON format');
    }
    self::$csrf = $str;
  }

  public static function isJson($string)
  {
    json_decode($string);

    return JSON_ERROR_NONE == json_last_error();
  }
}
