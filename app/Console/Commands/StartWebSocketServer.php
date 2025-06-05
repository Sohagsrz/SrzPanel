<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Ratchet\Server\IoServer;
use Ratchet\Http\HttpServer;
use Ratchet\WebSocket\WsServer;
use App\WebSocket\TerminalHandler;

class StartWebSocketServer extends Command
{
    protected $signature = 'websocket:serve {--port=8080}';
    protected $description = 'Start the WebSocket server for the terminal';

    public function handle()
    {
        $port = $this->option('port');
        
        $this->info("Starting WebSocket server on port {$port}...");
        
        $server = IoServer::factory(
            new HttpServer(
                new WsServer(
                    new TerminalHandler()
                )
            ),
            $port
        );
        
        $this->info("WebSocket server is running on port {$port}");
        $server->run();
    }
} 