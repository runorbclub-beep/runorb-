<?php
/**
 * Standalone Swoole WebSocket Server for PK
 * No Laravel dependency - uses raw Redis
 * Uses Swoole\Process for push poller (no pcntl_fork needed)
 */

// Redis connection helper
function getRedis($db = 13) {
    static $connections = [];
    if (!isset($connections[$db])) {
        $redis = new Redis();
        $redis->connect('127.0.0.1', 6379);
        $redis->select($db);
        $connections[$db] = $redis;
    }
    try {
        $connections[$db]->ping();
    } catch (Exception $e) {
        $redis = new Redis();
        $redis->connect('127.0.0.1', 6379);
        $redis->select($db);
        $connections[$db] = $redis;
    }
    return $connections[$db];
}

// Shared fd->room mapping table (Swoole\Table)
$fdRoomTable = new Swoole\Table(1024);
$fdRoomTable->column('pk_room_id', Swoole\Table::TYPE_STRING, 64);
$fdRoomTable->column('user_id', Swoole\Table::TYPE_STRING, 64);
$fdRoomTable->column('user_group', Swoole\Table::TYPE_STRING, 16);
$fdRoomTable->create();

// Push queue for worker processes to process
$pushQueue = new Swoole\Table(1024);
$pushQueue->column('payload', Swoole\Table::TYPE_STRING, 4096);
$pushQueue->column('processed', Swoole\Table::TYPE_INT);
$pushQueue->create();

// Create Swoole WebSocket server
$server = new Swoole\WebSocket\Server('0.0.0.0', 5202);

$server->set([
    'worker_num' => 1,
    'daemonize' => 1,
    'log_file' => '/www/wwwroot/websocket.hisport/storage/logs/pk_ws.log',
    'pid_file' => '/www/wwwroot/websocket.hisport/storage/logs/pk_ws.pid',
    'heartbeat_check_interval' => 60,
    'heartbeat_idle_time' => 300,
]);

// Add push poller as custom process
$pollerProcess = new Swoole\Process(function ($process) use ($server) {
    error_log("Push poller process started, pid=" . getmypid());
    
    while (true) {
        try {
            $redis = getRedis(14);
            while (true) {
                $message = $redis->lpop('pk_ws_push_queue');
                if (!$message) break;
                
                $payload = json_decode($message, true);
                if (!$payload || !isset($payload['pk_room_id'])) continue;
                
                error_log("Push poller received: room=" . $payload['pk_room_id'] . " event=" . ($payload['event'] ?? 'unknown'));
                
                // Send to workers via pipe
                $server->sendMessage(json_encode($payload), 0);
            }
        } catch (Throwable $e) {
            error_log("Push poller error: " . $e->getMessage());
        }
        
        usleep(300000); // 300ms
    }
}, false, 0, true);

$server->addProcess($pollerProcess);

// Handle pipe messages from poller process
$server->on('PipeMessage', function ($server, $srcWorkerId, $message) {
    $payload = json_decode($message, true);
    if (!$payload || !isset($payload['pk_room_id'])) return;
    
    $pk_room_id = $payload['pk_room_id'];
    $event = $payload['event'] ?? 'pkListChange';
    
    error_log("PipeMessage: room=" . $pk_room_id . " event=" . $event);
    
    // Get room data with fds
    $redis13 = getRedis(13);
    $roomData = $redis13->get($pk_room_id);
    if (!$roomData) return;
    
    $room = json_decode($roomData, true);
    if (!$room) return;
    
    $room['red'] = isset($room['red']) ? array_values($room['red']) : [];
    $room['blue'] = isset($room['blue']) ? array_values($room['blue']) : [];
    
    // Build the push message
    $returnData = [
        'code' => 1,
        'msg' => 'success',
        'data' => $payload['data'] ?? ['event' => $event]
    ];
    
    if (isset($payload['data']['list'])) {
        $returnData['data']['list'] = $payload['data']['list'];
    } else {
        $returnData['data']['list'] = $room;
    }
    
    // Push to all connected clients
    foreach (['red', 'blue'] as $group) {
        foreach ($room[$group] ?? [] as $user) {
            if (isset($user['fd']) && $user['fd'] != 0) {
                $msg = $returnData;
                $msg['data']['list']['user_group'] = $group;
                if ($server->isEstablished($user['fd'])) {
                    $server->push($user['fd'], json_encode($msg));
                    error_log("PipeMessage push to fd:" . $user['fd'] . " group:" . $group);
                }
            }
        }
    }
});

// Broadcast message to all users in a PK room
function formatDuration($seconds) {
    $minutes = floor($seconds / 60);
    $secs = $seconds % 60;
    return sprintf('%02d:%02d', $minutes, $secs);
}

function broadcastBetweenPlay($server, $pkRoomId) {
    $redis13 = getRedis(13);
    $room = json_decode($redis13->get($pkRoomId), true);
    if (!$room || ($room['status'] ?? 0) != 2) return;
    if (($room['pk_start_time'] ?? 0) + 9 > time()) return;

    $circleDistance = 16.5876089;

    $redCircleCount = 0;
    $redSpeed = [0, 0, 0, 0, 0];
    foreach ($room['red'] ?? [] as $node) {
        $circles = $node['circle_count'] ?? [0];
        $redCircleCount += (float)($circles[count($circles) - 1] ?? 0);
        $speed = $node['speed'] ?? [0, 0, 0, 0, 0];
        if (($speed[count($speed) - 1] ?? 0) > $redSpeed[count($redSpeed) - 1]) {
            $redSpeed = $speed;
        }
    }

    $blueCircleCount = 0;
    $blueSpeed = [0, 0, 0, 0, 0];
    foreach ($room['blue'] ?? [] as $node) {
        $circles = $node['circle_count'] ?? [0];
        $blueCircleCount += (float)($circles[count($circles) - 1] ?? 0);
        $speed = $node['speed'] ?? [0, 0, 0, 0, 0];
        if (($speed[count($speed) - 1] ?? 0) > $blueSpeed[count($blueSpeed) - 1]) {
            $blueSpeed = $speed;
        }
    }

    $redDistance = round($redCircleCount * $circleDistance / 100 / 1000, 3);
    $blueDistance = round($blueCircleCount * $circleDistance / 100 / 1000, 3);

    error_log("PK BETWEEN_PLAY BROADCAST: room=$pkRoomId red=$redDistance km blue=$blueDistance km");

    foreach ($room['red'] ?? [] as $node) {
        if (isset($node['fd']) && $node['fd'] != 0 && $server->isEstablished($node['fd'])) {
            $msg = json_encode([
                'code' => 1, 'msg' => 'success',
                'data' => [
                    'event' => 'between_play',
                    'list' => [
                        'user_group' => 'red',
                        'red_distance' => $redDistance,
                        'red_speed' => $redSpeed,
                        'blue_distance' => $blueDistance,
                        'blue_speed' => $blueSpeed,
                    ]
                ]
            ]);
            $server->push($node['fd'], $msg);
        }
    }

    foreach ($room['blue'] ?? [] as $node) {
        if (isset($node['fd']) && $node['fd'] != 0 && $server->isEstablished($node['fd'])) {
            $msg = json_encode([
                'code' => 1, 'msg' => 'success',
                'data' => [
                    'event' => 'between_play',
                    'list' => [
                        'user_group' => 'blue',
                        'red_distance' => $redDistance,
                        'red_speed' => $redSpeed,
                        'blue_distance' => $blueDistance,
                        'blue_speed' => $blueSpeed,
                    ]
                ]
            ]);
            $server->push($node['fd'], $msg);
        }
    }
}

function broadcastPkResult($server, $pkRoomId, $resultData) {
    $redis13 = getRedis(13);
    $room = json_decode($redis13->get($pkRoomId), true);
    if (!$room) return;

    error_log("PK RESULT BROADCAST: room=$pkRoomId data=" . json_encode($resultData));

    foreach ($room['red'] ?? [] as $node) {
        if (isset($node['fd']) && $node['fd'] != 0 && $server->isEstablished($node['fd'])) {
            $resultList = $resultData;
            $resultList['user_group'] = 'red';
            $msg = json_encode([
                'code' => 1, 'msg' => 'success',
                'data' => [
                    'event' => 'pkResult',
                    'list' => $resultList,
                ]
            ]);
            $server->push($node['fd'], $msg);
        }
    }

    foreach ($room['blue'] ?? [] as $node) {
        if (isset($node['fd']) && $node['fd'] != 0 && $server->isEstablished($node['fd'])) {
            $resultList = $resultData;
            $resultList['user_group'] = 'blue';
            $msg = json_encode([
                'code' => 1, 'msg' => 'success',
                'data' => [
                    'event' => 'pkResult',
                    'list' => $resultList,
                ]
            ]);
            $server->push($node['fd'], $msg);
        }
    }
}

function broadcastToRoom($server, $pk_room_id, $event, $extraData = []) {
    $redis = getRedis(13);
    $roomData = $redis->get($pk_room_id);
    if (!$roomData) return;
    
    $room = json_decode($roomData, true);
    if (!$room) return;
    
    $room['red'] = isset($room['red']) ? array_values($room['red']) : [];
    $room['blue'] = isset($room['blue']) ? array_values($room['blue']) : [];
    
    $returnData = [
        'code' => 1,
        'msg' => 'success',
        'data' => array_merge(['event' => $event], $extraData)
    ];
    
    if ($event === 'pkListChange' || $event === 'pkStart' || $event === 'pkResult') {
        if (isset($room['status']) && $room['status'] == 2) {
            $room['time_long'] = ($room['pk_stop_time'] ?? time()) - time();
        }
        $returnData['data']['list'] = $room;
    }
    
    foreach (['red', 'blue'] as $group) {
        foreach ($room[$group] ?? [] as $user) {
            if (isset($user['fd']) && $user['fd'] != 0) {
                $msg = $returnData;
                $msg['data']['list']['user_group'] = $group;
                if ($server->isEstablished($user['fd'])) {
                    $server->push($user['fd'], json_encode($msg));
                    error_log("Broadcast to fd:" . $user['fd'] . " group:" . $group . " event:" . $event);
                }
            }
        }
    }
}

// Handle pk_ready event
function handlePkReady($server, $data) {
    $redis = getRedis(13);
    $room = json_decode($redis->get($data['pk_room_id']), true);
    
    if (!$room) return 'pkListChange';
    if (!isset($room[$data['user_group']]) || !isset($room[$data['user_group']][$data['user_id']])) {
        return 'pkListChange';
    }
    
    $room[$data['user_group']][$data['user_id']]['is_ready'] = 1;
    $redis->setex($data['pk_room_id'], 86400, json_encode($room));
    
    $allReady = true;
    foreach ($room['red'] ?? [] as $user) {
        if (($user['is_ready'] ?? 0) == 0) { $allReady = false; break; }
    }
    foreach ($room['blue'] ?? [] as $user) {
        if (($user['is_ready'] ?? 0) == 0) { $allReady = false; break; }
    }
    
    if ($allReady && count($room['red'] ?? []) > 0 && count($room['blue'] ?? []) > 0) {
        $room['status'] = 2;
        $room['pk_start_time'] = time();
        $room['pk_stop_time'] = $room['pk_start_time'] + 9 + ($room['time_long'] ?? 0);
        $redis->setex($data['pk_room_id'], 86400, json_encode($room));
        error_log("PK START: room=" . $data['pk_room_id']);
        
        // Start auto-stop timer: fire when pk_stop_time is reached
        $delayMs = (($room['pk_stop_time'] - time()) * 1000) + 2500; // +2500ms buffer (wait for final between_play)
        $roomId = $data['pk_room_id'];
        Swoole\Timer::after($delayMs, function() use ($server, $roomId) {
            error_log("PK TIMER FIRED: room=" . $roomId);
            autoStopPk($server, $roomId);
        });
        error_log("PK TIMER SET: room=" . $data['pk_room_id'] . " delay=" . $delayMs . "ms");
        
        return 'pkStart';
    }
    
    return 'pkListChange';
}

// Handle pk_unready event
function handlePkUnReady($server, $data) {
    $redis = getRedis(13);
    $room = json_decode($redis->get($data['pk_room_id']), true);
    
    if (!$room) return;
    if (!isset($room[$data['user_group']]) || !isset($room[$data['user_group']][$data['user_id']])) return;
    
    $room[$data['user_group']][$data['user_id']]['is_ready'] = 0;
    $redis->setex($data['pk_room_id'], 86400, json_encode($room));
}

// Handle between_play event
function handleBetweenPlay($server, $data) {
    $redis = getRedis(13);
    $room = json_decode($redis->get($data['pk_room_id']), true);
    
    if (!$room) return 'between_play_error';
    if (!isset($room[$data['user_group']]) || !isset($room[$data['user_group']][$data['user_id']])) {
        return 'between_play_error';
    }
    
    $room[$data['user_group']][$data['user_id']]['circle_count'] = $data['circle_detail'] ?? [];
    $room[$data['user_group']][$data['user_id']]['speed'] = $data['speed_detail'] ?? [];
    
    if (isset($data['is_abnormal']) && $data['is_abnormal'] == 1) {
        $room[$data['user_group']][$data['user_id']]['is_abnormal'] = 1;
    }
    
    $redis->setex($data['pk_room_id'], 86400, json_encode($room));
    
    error_log("PK BETWEEN_PLAY: room=" . $data['pk_room_id'] . " user=" . $data['user_id'] . 
              " group=" . $data['user_group'] . " circles=" . json_encode($data['circle_detail'] ?? []));
    
    // Broadcast between_play in old format (red_distance/blue_distance/red_speed/blue_speed)
    broadcastBetweenPlay($server, $data['pk_room_id']);
    
    return 'between_play';
}

// Handle pk_stop event
// Auto-stop PK when timer fires (server-side, no App trigger needed)
function autoStopPk($server, $pk_room_id) {
    $redis13 = getRedis(13);
    $room = json_decode($redis13->get($pk_room_id), true);
    
    if (!$room || ($room['status'] ?? 0) != 2) {
        error_log("PK AUTO-STOP SKIP: room=" . $pk_room_id . " status=" . ($room['status'] ?? 'null'));
        return;
    }
    
    // Mark all users as stopped
    foreach (['red', 'blue'] as $group) {
        foreach ($room[$group] ?? [] as $userId => $user) {
            $room[$group][$userId]['is_stop'] = 1;
        }
    }
    
    $room['status'] = 3;
    $redis13->setex($pk_room_id, 86400, json_encode($room));
    
    // Calculate result
    $circleDistance = 16.5876089;
    $redCircleTotal = 0;
    $blueCircleTotal = 0;
    
    foreach ($room['red'] ?? [] as $user) {
        $circles = $user['circle_count'] ?? [];
        $lastCircle = (float)($circles[count($circles) - 1] ?? 0);
        $redCircleTotal += $lastCircle;
    }
    foreach ($room['blue'] ?? [] as $user) {
        $circles = $user['circle_count'] ?? [];
        $lastCircle = (float)($circles[count($circles) - 1] ?? 0);
        $blueCircleTotal += $lastCircle;
    }
    
    $redDuration = ($room['pk_stop_time'] ?? time()) - ($room['pk_start_time'] ?? time());
    $blueDuration = $redDuration;
    
    $groupWin = '';
    $pkResultType = $room['pk_result_type'] ?? 1;
    if ($pkResultType == 0) {
        $groupWin = $redDuration < $blueDuration ? 'red' : 'blue';
    } else {
        $groupWin = $redCircleTotal > $blueCircleTotal ? 'red' : 'blue';
    }
    
    $resultData = [
        'group_win' => $groupWin,
        'group_red_duration' => formatDuration($redDuration),
        'group_red_distance' => number_format($redCircleTotal * $circleDistance / 100 / 1000, 3),
        'group_blue_duration' => formatDuration($blueDuration),
        'group_blue_distance' => number_format($blueCircleTotal * $circleDistance / 100 / 1000, 3),
    ];
    
    error_log("PK AUTO-STOP RESULT: room=" . $pk_room_id . " win=" . $groupWin . 
              " red=" . $resultData['group_red_distance'] . "km blue=" . $resultData['group_blue_distance'] . "km" .
              " red_circles=" . $redCircleTotal . " blue_circles=" . $blueCircleTotal);
    
    // Broadcast pkResult in old format (data.list)
    broadcastPkResult($server, $pk_room_id, $resultData);
    
    // Async update MySQL via curl (Swoole 5.x removed Coroutine\HttpClient)
    $duration = ($room['pk_stop_time'] ?? time()) - ($room['pk_start_time'] ?? time());
    $postData = json_encode([
        'pk_room_id' => $pk_room_id,
        'group_win' => $groupWin,
        'duration' => $duration,
    ]);
    $ch = curl_init('http://127.0.0.1:8080/api/pk/internal/stop');
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $postData);
    curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 5);
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    error_log("PK MySQL UPDATE: room=" . $pk_room_id . " code=" . $httpCode . " response=" . substr($response ?: 'null', 0, 200));
}

function handlePkStop($server, $data) {
    $redis13 = getRedis(13);
    $room = json_decode($redis13->get($data['pk_room_id']), true);
    
    if (!$room) return 'pkListChange';
    
    // If autoStopPk already ended the match, just mark user as stopped and return
    if (($room['status'] ?? 0) == 3) {
        error_log("PK STOP SKIP (already ended): room=" . $data['pk_room_id'] . " user=" . $data['user_id']);
        return 'pkListChange';
    }
    
    // Mark user as stopped in Redis
    if (isset($room[$data['user_group']][$data['user_id']])) {
        $room[$data['user_group']][$data['user_id']]['is_stop'] = 1;
    }
    
    // Check if all users have stopped
    $allStopped = true;
    foreach (['red', 'blue'] as $group) {
        if (isset($room[$group]) && count($room[$group]) > 0) {
            foreach ($room[$group] as $user) {
                if (($user['is_stop'] ?? 0) == 0) { $allStopped = false; break 2; }
            }
        }
    }
    
    $room['status'] = 3; // PK ended
    $redis13->setex($data['pk_room_id'], 86400, json_encode($room));
    
    if ($allStopped) {
        error_log("PK ALL STOPPED: room=" . $data['pk_room_id'] . " - calculating result");
        
        // Calculate result from Redis data (circle_count based distance)
        $circleDistance = 16.5876089;
        $redCircleTotal = 0;
        $blueCircleTotal = 0;
        $redDuration = 0;
        $blueDuration = 0;
        
        foreach ($room['red'] ?? [] as $user) {
            $circles = $user['circle_count'] ?? [];
            $lastCircle = (float)($circles[count($circles) - 1] ?? 0);
            $redCircleTotal += $lastCircle;
        }
        foreach ($room['blue'] ?? [] as $user) {
            $circles = $user['circle_count'] ?? [];
            $lastCircle = (float)($circles[count($circles) - 1] ?? 0);
            $blueCircleTotal += $lastCircle;
        }
        
        // Duration from start to now
        $redDuration = time() - ($room['pk_start_time'] ?? time());
        $blueDuration = $redDuration;
        
        // Determine winner
        $groupWin = '';
        $pkResultType = $room['pk_result_type'] ?? 1;
        if ($pkResultType == 0) {
            $groupWin = $redDuration < $blueDuration ? 'red' : 'blue';
        } else {
            $groupWin = $redCircleTotal > $blueCircleTotal ? 'red' : 'blue';
        }
        
        $resultData = [
            'group_win' => $groupWin,
            'group_red_duration' => formatDuration($redDuration),
            'group_red_distance' => number_format($redCircleTotal * $circleDistance / 100 / 1000, 3),
            'group_blue_duration' => formatDuration($blueDuration),
            'group_blue_distance' => number_format($blueCircleTotal * $circleDistance / 100 / 1000, 3),
        ];
        
        error_log("PK RESULT: room=" . $data['pk_room_id'] . " win=" . $groupWin . 
                  " red_dist=" . $resultData['group_red_distance'] . 
                  " blue_dist=" . $resultData['group_blue_distance'] .
                  " red_circles=" . $redCircleTotal . " blue_circles=" . $blueCircleTotal);
        
        // Broadcast pkResult in old format (data.list)
        broadcastPkResult($server, $data['pk_room_id'], $resultData);
        
        // Update MySQL via curl (Swoole 6.x removed Coroutine\HttpClient)
        $duration2 = ($room['pk_stop_time'] ?? time()) - ($room['pk_start_time'] ?? time());
        $postData2 = json_encode([
            'pk_room_id' => $data['pk_room_id'],
            'group_win' => $groupWin,
            'duration' => $duration2,
        ]);
        $ch2 = curl_init('http://127.0.0.1:8080/api/pk/internal/stop');
        curl_setopt($ch2, CURLOPT_POST, true);
        curl_setopt($ch2, CURLOPT_POSTFIELDS, $postData2);
        curl_setopt($ch2, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
        curl_setopt($ch2, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch2, CURLOPT_TIMEOUT, 5);
        curl_exec($ch2);
        curl_close($ch2);
    }
    
    return 'pkListChange';
}

// Handle pk_cancel event
function handlePkCancel($server, $data) {
    $redis1 = getRedis(1);
    $redis1->rpush('user_pk_data', json_encode($data));
    
    $redis13 = getRedis(13);
    $room = json_decode($redis13->get($data['pk_room_id']), true);
    
    if (!$room) return 'pkListChange';
    
    unset($room[$data['user_group']][$data['user_id']]);
    $redis13->setex($data['pk_room_id'], 86400, json_encode($room));
    
    return 'pkListChange';
}

// Handle bind_again (reconnect)
function handleBindAgain($server, $fd, $data) {
    $redis = getRedis(13);
    $room = json_decode($redis->get($data['pk_room_id']), true);
    
    if (!$room) return;
    
    if ($room['status'] == 2) {
        $newTimeLong = time() - $room['pk_start_time'];
    } else {
        $newTimeLong = time() - $room['pk_start_time'] + 9;
    }
    $room['time_long'] = $newTimeLong;
    
    $circleDistance = 16.5876089;
    $redCircleCount = 0;
    $blueCircleCount = 0;
    
    foreach ($room['red'] ?? [] as $node) {
        $circles = $node['circle_count'] ?? [];
        $redCircleCount += $circles[count($circles) - 1] ?? 0;
    }
    foreach ($room['blue'] ?? [] as $node) {
        $circles = $node['circle_count'] ?? [];
        $blueCircleCount += $circles[count($circles) - 1] ?? 0;
    }
    
    $room['red_distance'] = round($redCircleCount * $circleDistance / 100 / 1000, 3);
    $room['blue_distance'] = round($blueCircleCount * $circleDistance / 100 / 1000, 3);
    $room['user_group'] = $data['user_group'];
    
    $returnData = [
        'code' => 1,
        'msg' => 'success',
        'data' => [
            'event' => 'bind_again',
            'list' => $room
        ]
    ];
    
    $server->push($fd, json_encode($returnData));
}

$server->on('Open', function ($server, $request) use ($fdRoomTable) {
    $fd = $request->fd;
    error_log("WS Open: fd=" . $fd);
    
    $language = $request->header['language'] ?? 'zh-CN';
    $pkDataRaw = $request->header['pkdata'] ?? '';
    $pkData = json_decode($pkDataRaw, true);
    
    error_log("WS Open: fd=" . $fd . " pkdata=" . $pkDataRaw);
    
    $testUserPlay = $pkData['test_user_play'] ?? '';
    if ($testUserPlay != '') {
        $redis = getRedis(13);
        $redis->setex($testUserPlay, 86400, $fd);
        $server->push($fd, json_encode(['code' => 1, 'msg' => 'success', 'event' => 'connection_success', 'data' => ['fd' => $fd]]));
        return;
    }
    
    $pkRoomId = $pkData['pk_room_id'] ?? '';
    if ($pkRoomId == '') {
        $server->push($fd, json_encode(['code' => 0, 'event' => 'error', 'msg' => 'Missing pk_room_id']));
        return;
    }
    
    $redis = getRedis(13);
    $room = json_decode($redis->get($pkRoomId), true);
    
    // Store fd mapping
    $redis12 = getRedis(12);
    $redis12->setex($fd, 18000, $pkDataRaw);
    
    if (!$room) {
        error_log("WS Open: room not found fd=" . $fd . " room=" . $pkRoomId);
        $server->push($fd, json_encode(['code' => 0, 'event' => 'error', 'msg' => 'Room not found']));
        return;
    }
    
    $userGroup = $pkData['user_group'] ?? '';
    $userId = $pkData['user_id'] ?? '';
    
    if ($userGroup && $userId && isset($room[$userGroup]) && isset($room[$userGroup][$userId])) {
        $room[$userGroup][$userId]['fd'] = $fd;
        $redis->setex($pkRoomId, 86400, json_encode($room));
        error_log("WS Open: stored fd=" . $fd . " for user=" . $userId . " group=" . $userGroup . " room=" . $pkRoomId);
        
        broadcastToRoom($server, $pkRoomId, 'pkListChange');
    }
    
    $server->push($fd, json_encode(['code' => 1, 'msg' => 'success', 'event' => 'connection_success', 'data' => ['fd' => $fd]]));
    error_log("WS Open: success fd=" . $fd);
});

$server->on('Message', function ($server, $frame) {
    error_log("WS Message: fd=" . $frame->fd . " data=" . substr($frame->data, 0, 200));
    
    $data = json_decode($frame->data, true);
    if (!$data) return;
    
    if (($data['event'] ?? '') === 'heartbeat') return;
    
    $event = $data['event'] ?? '';
    $pkRoomId = $data['pk_room_id'] ?? '';
    
    if (!$pkRoomId) return;
    
    $msgEvent = '';
    
    switch ($event) {
        case 'between_play':
            $msgEvent = handleBetweenPlay($server, $data);
            if ($msgEvent === 'between_play_error') {
                $server->push($frame->fd, json_encode([
                    'code' => 1, 'msg' => 'success',
                    'data' => ['event' => 'between_play_error', 'msg' => 'User Not Found']
                ]));
            }
            break;
            
        case 'pk_ready':
            $msgEvent = handlePkReady($server, $data);
            break;
            
        case 'pk_unready':
            handlePkUnReady($server, $data);
            $msgEvent = 'pkListChange';
            break;
            
        case 'pk_stop':
            $msgEvent = handlePkStop($server, $data);
            break;
            
        case 'bind_again':
            handleBindAgain($server, $frame->fd, $data);
            return;
            
        default:
            $msgEvent = handlePkCancel($server, $data);
            break;
    }
    
    error_log("WS Message: event=" . $event . " -> msgEvent=" . $msgEvent);
    
    if ($msgEvent && $msgEvent !== 'between_play') {
        broadcastToRoom($server, $pkRoomId, $msgEvent);
    }
});

$server->on('Close', function ($server, $fd) use ($fdRoomTable) {
    error_log("WS Close: fd=" . $fd);
});

$server->on('WorkerStart', function ($server, $workerId) {
    error_log("Worker started: id=" . $workerId);
});

echo "Starting PK WebSocket server...\n";
$server->start();

