<?php

namespace App\Http\CommonClass;

/**
 * 雪花算法 ID 生成器（兼容 Swoole 6.x，使用 flock 替代 swoole_lock）
 * Class Snowflakes
 * @package App\Http\CommonClass
 * User: zxw
 * Date: 2021/11/29 16:09
 */
class SnowFlakeSwooles
{
    const EPOCH = 1543223810238;    // 起始时间戳，毫秒

    const SEQUENCE_BITS = 12;   //序号部分12位
    const SEQUENCE_MAX = -1 ^ (-1 << self::SEQUENCE_BITS);  // 序号最大值

    const WORKER_BITS = 10; // 节点部分10位
    const WORKER_MAX = -1 ^ (-1 << self::WORKER_BITS);  // 节点最大数值

    const TIME_SHIFT = self::WORKER_BITS + self::SEQUENCE_BITS; // 时间戳部分左偏移量
    const WORKER_SHIFT = self::SEQUENCE_BITS;   // 节点部分左偏移量

    protected $timestamp;   // 上次ID生成时间戳
    protected $workerId;    // 节点ID
    protected $sequence;    // 序号
    protected $lockFile;    // flock 文件句柄

    public function __construct($workerId)
    {
        if ($workerId < 0 || $workerId > self::WORKER_MAX) {
            trigger_error("Worker ID 超出范围");
            exit(0);
        }

        $this->timestamp = 0;
        $this->workerId = $workerId;
        $this->sequence = 0;

        // Use flock instead of swoole_lock (swoole_lock removed in Swoole 6.x)
        $lockDir = sys_get_temp_dir();
        $this->lockFile = fopen($lockDir . '/snowflake_' . getmypid() . '.lock', 'c+');
        if (!$this->lockFile) {
            trigger_error("Cannot create lock file for SnowFlake");
            exit(0);
        }
    }

    /**
     * 生成ID
     * @return int
     */
    public function getId()
    {
        flock($this->lockFile, LOCK_EX);    // 加锁
        $now = $this->now();
        if ($this->timestamp == $now) {
            $this->sequence++;

            if ($this->sequence > self::SEQUENCE_MAX) {
                // 当前毫秒内生成的序号已经超出最大范围，等待下一毫秒重新生成
                while ($now <= $this->timestamp) {
                    $now = $this->now();
                }
            }
        } else {
            $this->sequence = 0;
        }

        $this->timestamp = $now;    // 更新ID生时间戳

        $id = (($now - self::EPOCH) << self::TIME_SHIFT) | ($this->workerId << self::WORKER_SHIFT) | $this->sequence;
        flock($this->lockFile, LOCK_UN);  //解锁

        return $id;
    }

    /**
     * 获取当前毫秒
     * @return string
     */
    public function now()
    {
        return sprintf("%.0f", microtime(true) * 1000);
    }

    public function __destruct()
    {
        if ($this->lockFile) {
            fclose($this->lockFile);
        }
    }
}
