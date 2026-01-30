#!/usr/bin/env php
<?php
/**
 * WebSocket Server using Workerman
 * Real-time chat server for Flutter app
 */

use Workerman\Worker;
use WsServer\WsHandlers;

require_once __DIR__ . '/vendor/autoload.php';

// Create WebSocket worker
$ws_worker = new Worker('websocket://0.0.0.0:8080');

// Set worker name
$ws_worker->name = 'FlutterChatWS';

// Number of processes
$ws_worker->count = 4;

// Handler instance
$handlers = null;

// When worker starts
$ws_worker->onWorkerStart = function($worker) use (&$handlers) {
    echo "WebSocket Server started on ws://0.0.0.0:8080\n";
    echo "Workers: {$worker->count}\n";
    echo "Waiting for connections...\n\n";
    
    // Create handlers instance
    $handlers = new WsHandlers($worker);
};

// When a client connects
$ws_worker->onConnect = function($connection) use (&$handlers) {
    $handlers->onConnect($connection);
};

// When a message is received
$ws_worker->onMessage = function($connection, $data) use (&$handlers) {
    $handlers->onMessage($connection, $data);
};

// When connection is closed
$ws_worker->onClose = function($connection) use (&$handlers) {
    $handlers->onClose($connection);
};

// When worker stops
$ws_worker->onWorkerStop = function($worker) {
    echo "WebSocket Server stopped\n";
};

// Run the worker
Worker::runAll();
