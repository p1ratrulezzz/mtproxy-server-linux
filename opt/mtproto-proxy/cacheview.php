<?php
/**
 * Cache monitor utility.
 * Run php cacheview.php
 */

require_once 'vendor/autoload.php';

/**
 * @link: https://stackoverflow.com/a/30101904
 */
function clearStdin()
{
  // exec('clear');
  for ($i = 0; $i < 50; $i++) echo "\r\n";
}

/**
 * @param $message
 * @param null $force_clear_lines
 * @link: https://stackoverflow.com/a/27850902/5242972
 */
function replaceable_echo($message, $force_clear_lines = NULL) {
  static $last_lines = 0;

  if(!is_null($force_clear_lines)) {
    $last_lines = $force_clear_lines;
  }

  $term_width = exec('tput cols', $toss, $status);
  if($status) {
    $term_width = 64; // Arbitrary fall-back term width.
  }

  $line_count = 0;
  foreach(explode("\n", $message) as $line) {
    $line_count += count(str_split($line, $term_width));
  }

  // Erasure MAGIC: Clear as many lines as the last output had.
  for($i = 0; $i < $last_lines; $i++) {
    // Return to the beginning of the line
    echo "\r";
    // Erase to the end of the line
    echo "\033[K";
    // Move cursor Up a line
    echo "\033[1A";
    // Return to the beginning of the line
    echo "\r";
    // Erase to the end of the line
    echo "\033[K";
    // Return to the beginning of the line
    echo "\r";
    // Can be consolodated into
    // echo "\r\033[K\033[1A\r\033[K\r";
  }

  $last_lines = $line_count;

  echo $message."\n";
}

define('EOL_CR', PHP_EOL);
while (true) {
  ob_start();
  // $cachepool = new \ByJG\Cache\Psr6\CachePool(new \ByJG\Cache\Psr16\FileSystemCacheEngine('cache_mtproxy'));
  $cachepool = new \ByJG\Cache\Psr6\CachePool(new \ByJG\Cache\Psr16\ShmopCacheEngine(['prefix' => 'mtproxy_']), 0);
  $item_daemon_ids = $cachepool->getItem('daemon_ids');
  if ($item_daemon_ids->isHit()) {
    foreach ($item_daemon_ids->get() as $id) {
      $item_pids = $cachepool->getItem('daemon:' . $id);

      echo "Daemon: {$id}" . EOL_CR;
      if ($item_pids->isHit()) {
        foreach ($item_pids->get() as $pid) {
          echo "--Pid: {$pid}" . EOL_CR;
        }
      }
      else {
        echo "-- is empty --" . EOL_CR;
      }

      unset($item_pids);
    }
  }

  unset($item_daemon_ids);
  unset($cachepool);

  echo date('c') . PHP_EOL;
  $data = ob_get_clean();
  replaceable_echo($data);
  sleep(1);
  //echo "clear";
  //echo chr(27).chr(91).'H'.chr(27).chr(91).'J';
}
