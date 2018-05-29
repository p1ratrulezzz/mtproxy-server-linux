<?php

/*
Copyright 2016-2018 Daniil Gentili
(https://daniil.it)
This file is part of MadelineProto.
MadelineProto is free software: you can redistribute it and/or modify it under the terms of the GNU Affero General Public License as published by the Free Software Foundation, either version 3 of the License, or (at your option) any later version.
The PWRTelegram API is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY; without even the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
See the GNU Affero General Public License for more details.
You should have received a copy of the GNU General Public License along with MadelineProto.
If not, see <http://www.gnu.org/licenses/>.
*/

namespace danog\MadelineProto;

/*
 * Socket server for multi-language API
 */
use ByJG\Cache\Psr16\FileSystemCacheEngine;
use ByJG\Cache\Psr16\ShmopCacheEngine;
use ByJG\Cache\Psr6\CachePool;
use malkusch\lock\mutex\FlockMutex;

class ServerProxy extends Server {

  private $settings;

  private $pids = [];

  private $mypid;

  /**
   * @var \malkusch\lock\mutex\LockMutex
   */
  protected $mutex;

  protected $daemon_id = null;

  public function __construct($settings)
  {
    set_error_handler(['\\danog\\MadelineProto\\Exception', 'ExceptionErrorHandler']);
    \danog\MadelineProto\Logger::constructor(3);
    if (!extension_loaded('sockets')) {
      throw new Exception(['extension', 'sockets']);
    }
    if (!extension_loaded('pcntl')) {
      throw new Exception(['extension', 'pcntl']);
    }
    $this->settings = $settings;
    $this->mypid = getmypid();

    $this->mutex = new FlockMutex(fopen(tempnam(sys_get_temp_dir(), 'flock'), 'w'));

    $this->daemon_id = $settings['daemon_id'];
    // $this->cachepool = new CachePool(new FileSystemCacheEngine('cache_mtproxy'));

    $this->log("Daemon id is {$this->daemon_id}");

    $self = $this;
    $this->mutex->synchronized(function () use (&$self) {
      return $self->daemonInit();
    });

    $this->cleanOldPids();
    $this->addPid($this->mypid);
  }

  /**
   * @return \ByJG\Cache\Psr6\CachePool
   */
  protected function cachePool() {
    return new CachePool(new ShmopCacheEngine(['prefix' => 'mtproxy_']), 0);
  }

  public function daemonInit() {
    $item = $this->cachePool()->getItem('daemon_ids');
    $daemon_ids = $item->isHit() ? $item->get() : [];
    $daemon_ids[$this->daemon_id] = $this->daemon_id;

    $item->set($daemon_ids);
    $this->cachePool()->save($item);
  }

  public function addPid($pid) {
    $pool = $this->cachePool();
    $self = $this;
    $this->mutex->synchronized(function() use ($pid, $pool, &$self) {
      $item = $pool->getItem('daemon:' . $this->daemon_id);
      $pids = $item->isHit() ? $item->get() : [];
      $pids[$pid] = $pid;
      $item->set($pids);

      $pool->save($item);

      $self->log("Added pid {$pid}");
    });
  }

  public function delPid($pid) {
    $pool = $this->cachePool();
    $self = $this;

    $this->mutex->synchronized(function() use ($pid, $pool, &$self) {
      $item = $pool->getItem('daemon:' . $this->daemon_id);
      $pids = $item->isHit() ? $item->get() : [];
      if (isset($pids[$pid])) {
        unset($pids[$pid]);
        $item->set($pids);

        $pool->save($item);

        $self->log("Deleted process {$pid}");
      }
      else {
        $self->log("Process {$pid} doesn't exists");
      }

    });
  }

  public function log($param, $level = \danog\MadelineProto\Logger::NOTICE) {
    return \danog\MadelineProto\Logger::log($param, $level);
  }

  protected function cleanOldPids() {
    $self = $this;
    return $this->mutex->synchronized(function () use (&$self) {
      return $self->cleanOldPidsUnsync();
    });
  }

  protected function cleanOldPidsUnsync() {
    $pool = $this->cachePool();
    $item_daemon_ids = $pool->getItem('daemon_ids');
    if ($item_daemon_ids->isHit()) {
      $ids = $item_daemon_ids->get();

      foreach ($ids as $key => $id) {
        if ($id == $this->daemon_id) {
          continue;
        }

        $cid = 'daemon:' . $id;

        $item = $pool->getItem($cid);
        if ($item->isHit()) {
          $pids = $item->get();
          foreach ($pids as $pid) {
           $kill_status = posix_kill($pid, SIGTERM) === true ? 'killed' : 'not killed';
           $this->log("Process {$pid} was " . $kill_status);
           if (-1 !== pcntl_waitpid($pid, $status, WNOHANG)) {
             $this->log("Process {$pid} finished");
           }
           else {
             $this->log("Can't wait for process {$pid}");
           }
          }

          $pool->deleteItem($item->getKey());
        }

        unset($ids[$key]);
      }

      $item_daemon_ids->set($ids);
      $pool->save($item_daemon_ids);
    }
  }

  public function start() {
    pcntl_signal(SIGTERM, [$this, 'sig_handler']);
    pcntl_signal(SIGINT, [$this, 'sig_handler']);
    pcntl_signal(SIGCHLD, [$this, 'sig_handler']);
    $this->sock = new \Socket($this->settings['type'], SOCK_STREAM, $this->settings['protocol']);
    $this->sock->bind($this->settings['address'], $this->settings['port']);
    $this->sock->listen();
    $this->sock->setBlocking(TRUE);
    $timeout = 10;
    $this->sock->setOption(\SOL_SOCKET, \SO_RCVTIMEO, $timeout);
    $this->sock->setOption(\SOL_SOCKET, \SO_SNDTIMEO, $timeout);
    \danog\MadelineProto\Logger::log('Server started! Listening on ' . $this->settings['address'] . ':' . $this->settings['port']);
    while (TRUE) {
      pcntl_signal_dispatch();
      try {
        if ($sock = $this->sock->accept()) {
          $this->handle($sock, $unlimited_forks);
        }
      }
      catch (\danog\MadelineProto\Exception $e) {}
    }
  }

  private function handle($socket, $unlimited_forks = true) {
    $forks = $this->cachePool()->getItem('daemon:' . $this->daemon_id)->get();
    $forks = $forks ? $forks : [];

    $pid = getmypid();
    // @fixme: Set in config.inc
    if ($unlimited_forks || count($forks) < 8) {
      $pid = pcntl_fork();

      if ($pid > 0) {
        $this->addPid($pid);
        return $this->pids[] = $pid;
      }
    }
    else {
      $this->log('Too many forks are created already. Won\'t fork');
    }

    if ($pid === -1) {
      die('could not fork');
    }
    elseif ($pid === 0) {
      // Child process
      pcntl_wait($status);
    }

    try {
      /**
       * @var $handler \danog\MadelineProto\Server\Proxy
       */
      $handler = new $this->settings['handler']($socket, $this->settings['extra'], NULL, NULL, NULL, NULL, NULL);
      if (method_exists($handler, 'setServer')) {
        $handler->setServer($this);
      }

      $handler->loop();
    }
    catch (\Exception $e) {
    }

    // Only kill process if it is not the main process
    if ($this->mypid != getmypid()) {
      $this->delPid(getmypid());
      die;
    }
  }

  public function __destruct() {
    if ($this->mypid === getmypid()) {
      \danog\MadelineProto\Logger::log('Shutting main process ' . $this->mypid . ' down');
      unset($this->sock);
      foreach ($this->pids as $pid) {
        \danog\MadelineProto\Logger::log("Waiting for {$pid}");
        pcntl_wait($pid);
        $this->delPid($pid);
      }

      $this->delPid($this->mypid);
      \danog\MadelineProto\Logger::log('Done, closing main process');

      $this->cleanOldPids();
      return;
    }
  }

  public function sig_handler($sig)
  {
    switch ($sig) {
      case SIGTERM:
      case SIGINT:
      case SIGKILL:
        $this->delPid(getmypid());
        exit;
      case SIGCHLD:
        pcntl_waitpid(-1, $status);
        $this->delPid(getmypid());
        break;
    }
  }
}