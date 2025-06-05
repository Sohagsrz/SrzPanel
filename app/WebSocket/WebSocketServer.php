<?php

namespace App\WebSocket;

use Illuminate\Support\Facades\Log;
use App\Models\User;

class WebSocketServer
{
    protected $server;
    protected $clients = [];
    protected $users = [];
    protected $port;
    protected $host;

    public function __construct($host = '0.0.0.0', $port = 8080)
    {
        $this->host = $host;
        $this->port = $port;
    }

    public function start()
    {
        $this->server = socket_create(AF_INET, SOCK_STREAM, SOL_TCP);
        socket_set_option($this->server, SOL_SOCKET, SO_REUSEADDR, 1);
        socket_bind($this->server, $this->host, $this->port);
        socket_listen($this->server);
        socket_set_nonblock($this->server);

        Log::info("WebSocket server started on {$this->host}:{$this->port}");

        while (true) {
            $this->handleConnections();
            $this->handleMessages();
            usleep(100000); // Sleep for 100ms to prevent CPU overload
        }
    }

    protected function handleConnections()
    {
        if ($client = @socket_accept($this->server)) {
            $this->clients[] = $client;
            $this->performHandshake($client);
            Log::info("New client connected");
        }
    }

    protected function handleMessages()
    {
        foreach ($this->clients as $index => $client) {
            $data = @socket_read($client, 1024, PHP_NORMAL_READ);
            
            if ($data === false) {
                $this->removeClient($index);
                continue;
            }

            if (!empty($data)) {
                $this->processMessage($client, $data);
            }
        }
    }

    protected function performHandshake($client)
    {
        $request = socket_read($client, 5000);
        preg_match('#Sec-WebSocket-Key: (.*)\r\n#', $request, $matches);
        $key = base64_encode(pack('H*', sha1($matches[1] . '258EAFA5-E914-47DA-95CA-C5AB0DC85B11')));
        $headers = "HTTP/1.1 101 Switching Protocols\r\n";
        $headers .= "Upgrade: websocket\r\n";
        $headers .= "Connection: Upgrade\r\n";
        $headers .= "Sec-WebSocket-Accept: $key\r\n\r\n";
        socket_write($client, $headers, strlen($headers));
    }

    protected function processMessage($client, $data)
    {
        $decodedData = $this->decode($data);
        if (!$decodedData) return;

        $message = json_decode($decodedData, true);
        if (!$message || !isset($message['type'])) return;

        switch ($message['type']) {
            case 'auth':
                $this->handleAuth($client, $message);
                break;
            case 'terminal':
                $this->handleTerminal($client, $message);
                break;
        }
    }

    protected function handleAuth($client, $message)
    {
        if (!isset($message['token'])) {
            $this->send($client, [
                'type' => 'error',
                'message' => 'Authentication token required'
            ]);
            return;
        }

        try {
            $user = User::where('api_token', $message['token'])->first();
            if (!$user) {
                throw new \Exception('Invalid token');
            }

            $this->users[spl_object_hash($client)] = $user;
            $this->send($client, [
                'type' => 'auth',
                'status' => 'success',
                'user' => [
                    'id' => $user->id,
                    'name' => $user->name,
                    'role' => $user->role
                ]
            ]);
        } catch (\Exception $e) {
            $this->send($client, [
                'type' => 'error',
                'message' => $e->getMessage()
            ]);
        }
    }

    protected function handleTerminal($client, $message)
    {
        $clientId = spl_object_hash($client);
        if (!isset($this->users[$clientId])) {
            $this->send($client, [
                'type' => 'error',
                'message' => 'Not authenticated'
            ]);
            return;
        }

        $user = $this->users[$clientId];
        
        if (!isset($message['command'])) {
            $this->send($client, [
                'type' => 'error',
                'message' => 'Command required'
            ]);
            return;
        }

        try {
            $process = new \Symfony\Component\Process\Process(['bash', '-c', $message['command']]);
            $process->setTty(true);
            
            $process->run(function ($type, $buffer) use ($client) {
                $this->send($client, [
                    'type' => $type === \Symfony\Component\Process\Process::ERR ? 'error' : 'output',
                    'content' => $buffer
                ]);
            });

            if (!$process->isSuccessful()) {
                throw new \Symfony\Component\Process\Exception\ProcessFailedException($process);
            }
        } catch (\Exception $e) {
            $this->send($client, [
                'type' => 'error',
                'message' => $e->getMessage()
            ]);
        }
    }

    protected function send($client, $data)
    {
        $encoded = $this->encode(json_encode($data));
        socket_write($client, $encoded, strlen($encoded));
    }

    protected function decode($data)
    {
        $len = ord($data[1]) & 127;
        if ($len === 126) {
            $masks = substr($data, 4, 4);
            $data = substr($data, 8);
        } elseif ($len === 127) {
            $masks = substr($data, 10, 4);
            $data = substr($data, 14);
        } else {
            $masks = substr($data, 2, 4);
            $data = substr($data, 6);
        }
        $text = '';
        for ($i = 0; $i < strlen($data); ++$i) {
            $text .= $data[$i] ^ $masks[$i % 4];
        }
        return $text;
    }

    protected function encode($text)
    {
        $b1 = 0x80 | (0x1 & 0x0f);
        $length = strlen($text);

        if ($length <= 125)
            $header = pack('CC', $b1, $length);
        elseif ($length > 125 && $length < 65536)
            $header = pack('CCn', $b1, 126, $length);
        else
            $header = pack('CCNN', $b1, 127, $length);

        return $header.$text;
    }

    protected function removeClient($index)
    {
        $client = $this->clients[$index];
        $clientId = spl_object_hash($client);
        unset($this->users[$clientId]);
        unset($this->clients[$index]);
        socket_close($client);
        Log::info("Client disconnected");
    }

    public function __destruct()
    {
        foreach ($this->clients as $client) {
            socket_close($client);
        }
        socket_close($this->server);
    }
} 