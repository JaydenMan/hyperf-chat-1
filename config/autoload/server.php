<?php

declare(strict_types=1);

/**
 * This file is part of Hyperf.
 *
 * @link     https://www.hyperf.io
 * @document https://hyperf.wiki
 * @contact  group@hyperf.io
 * @license  https://github.com/hyperf/hyperf/blob/master/LICENSE
 */

use Hyperf\Server\Server;
use Hyperf\Server\SwooleEvent;

/**
 * 设置http,websocket服务，
 * 外部访问，设置nginx 代理
 *
 * upstream hyperfhttp {
 *         server 127.0.0.1:9503;
 * }
 *
 * server {
 * # 监听端口
 * listen 11111;
 * # 绑定的域名，填写您的域名
 * server_name 192.168.216.130;
 *
 * location / {
 * client_max_body_size    20m;
 * # 将客户端的 Host 和 IP 信息一并转发到对应节点
 * proxy_set_header Host $http_host;
 * proxy_set_header X-Real-IP $remote_addr;
 * proxy_set_header X-Forwarded-For $proxy_add_x_forwarded_for;
 *
 * # 转发Cookie，设置 SameSite
 * proxy_cookie_path / "/; secure; HttpOnly; SameSite=strict";
 *
 * # 执行代理访问真实服务器
 * proxy_pass http://hyperfhttp;
 * }
 * }
 */
return [
    'mode' => SWOOLE_PROCESS,
    'servers' => [
        [
            'name' => 'http',
            'type' => Server::SERVER_HTTP,
            'host' => '0.0.0.0',
            'port' => 9503,
            'sock_type' => SWOOLE_SOCK_TCP,
            'callbacks' => [
                SwooleEvent::ON_REQUEST => [Hyperf\HttpServer\Server::class, 'onRequest'],
            ],
        ],
        [
            'name' => 'ws',
            'type' => Server::SERVER_WEBSOCKET,
            'host' => '0.0.0.0',
            'port' => 9504,
            'sock_type' => SWOOLE_SOCK_TCP,
            'callbacks' => [
                // 自定义握手处理
                SwooleEvent::ON_HAND_SHAKE => [Hyperf\WebSocketServer\Server::class, 'onHandShake'],
                SwooleEvent::ON_MESSAGE => [Hyperf\WebSocketServer\Server::class, 'onMessage'],
                SwooleEvent::ON_CLOSE => [Hyperf\WebSocketServer\Server::class, 'onClose'],
            ],
            'settings' => [
                //设置心跳检测
                'heartbeat_idle_time' => 70,
                'heartbeat_check_interval' => 30,
            ]
        ],
    ],
    'settings' => [
        'enable_coroutine' => true,
        'worker_num' => swoole_cpu_num() * 4,
        'pid_file' => BASE_PATH . '/runtime/hyperf.pid',
        'open_tcp_nodelay' => true,
        'max_coroutine' => 100000,
        'open_http2_protocol' => true,
        'max_request' => 10000,
        'socket_buffer_size' => 3 * 1024 * 1024,
        'buffer_output_size' => 3 * 1024 * 1024,
        'package_max_length' => 10 * 1024 * 1024,
    ],
    'callbacks' => [
        //自定义启动前事件
        SwooleEvent::ON_BEFORE_START => [App\Bootstrap\ServerStart::class, 'beforeStart'],
        SwooleEvent::ON_WORKER_START => [Hyperf\Framework\Bootstrap\WorkerStartCallback::class, 'onWorkerStart'],
        SwooleEvent::ON_PIPE_MESSAGE => [Hyperf\Framework\Bootstrap\PipeMessageCallback::class, 'onPipeMessage'],
        SwooleEvent::ON_WORKER_EXIT => [Hyperf\Framework\Bootstrap\WorkerExitCallback::class, 'onWorkerExit'],
    ],
];
