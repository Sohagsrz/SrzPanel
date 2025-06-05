<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\WebSocket\WebSocketServer;

class WebSocketServerCommand extends Command
{
    protected $signature = 'websocket:serve {--host=0.0.0.0} {--port=8080}';
    protected $description = 'Start the WebSocket server';

    public function handle()
    {
        $host = $this->option('host');
        $port = $this->option('port');
        
        $this->info("Starting WebSocket server on {$host}:{$port}...");
        
        try {
            $server = new WebSocketServer($host, $port);
            $server->start();
        } catch (\Exception $e) {
            $this->error("Failed to start WebSocket server: " . $e->getMessage());
            return 1;
        }
    }
} 