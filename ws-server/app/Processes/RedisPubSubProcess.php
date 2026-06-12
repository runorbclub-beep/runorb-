<?php

namespace App\Processes;

use Hhxsv5\LaravelS\Swoole\Process\CustomProcessInterface;
use Swoole\Http\Server;
use Swoole\Process;
use Illuminate\Support\Facades\Redis;

/**
 * Redis polling process for PK WebSocket push
 * Polls a Redis list for push messages instead of pub/sub
 */
class RedisPubSubProcess implements CustomProcessInterface
{
    public static function callback(Server $swoole, Process $process)
    {
        // Bootstrap Laravel
        $app = require base_path('bootstrap/app.php');
        $kernel = $app->make(\Illuminate\Contracts\Console\Kernel::class);
        $kernel->bootstrap();

        echo "Redis polling process started\n";

        while (true) {
            try {
                // Poll the push queue (non-blocking)
                Redis::select(14);
                while (true) {
                    $message = Redis::lpop('pk_ws_push_queue');
                    if (!$message) {
                        break;
                    }

                    echo "Received push: " . substr($message, 0, 100) . "\n";

                    $payload = json_decode($message, true);
                    if (!$payload || !isset($payload['pk_room_id'])) {
                        continue;
                    }

                    $pk_room_id = $payload['pk_room_id'];

                    // Get room data from Redis
                    Redis::select(13);
                    $roomData = Redis::get($pk_room_id);

                    if (!$roomData) continue;

                    $room = json_decode($roomData, true);
                    if (!$room) continue;

                    $jsonMessage = json_encode($payload);

                    foreach (['red', 'blue'] as $group) {
                        if (isset($room[$group])) {
                            foreach ($room[$group] as $user) {
                                if (isset($user['fd']) && $user['fd'] != 0) {
                                    if ($swoole->isEstablished($user['fd'])) {
                                        $swoole->push($user['fd'], $jsonMessage);
                                        echo "Pushed to fd: " . $user['fd'] . "\n";
                                    }
                                }
                            }
                        }
                    }
                }
            } catch (\Throwable $e) {
                echo "Redis polling error: " . $e->getMessage() . "\n";
            }

            // Sleep 500ms between polls
            usleep(500000);
        }
    }

    public static function onReload(Server $swoole, Process $process)
    {
        // Nothing to do on reload
    }
}
