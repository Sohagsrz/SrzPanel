<?php

namespace App\WebSocket;

use Ratchet\MessageComponentInterface;
use Ratchet\ConnectionInterface;
use Symfony\Component\Process\Process;
use Symfony\Component\Process\Exception\ProcessFailedException;
use App\Models\User;
use Illuminate\Support\Facades\Auth;

class TerminalHandler implements MessageComponentInterface
{
    protected $clients;
    protected $processes;
    protected $allowedCommands;

    public function __construct()
    {
        $this->clients = new \SplObjectStorage;
        $this->processes = [];
        $this->allowedCommands = [
            'user' => [
                'ls', 'cd', 'pwd', 'cat', 'less', 'tail', 'head',
                'grep', 'find', 'mkdir', 'touch', 'rm', 'cp', 'mv',
                'chmod', 'chown', 'tar', 'gzip', 'gunzip', 'zip', 'unzip'
            ],
            'reseller' => [
                'ls', 'cd', 'pwd', 'cat', 'less', 'tail', 'head',
                'grep', 'find', 'mkdir', 'touch', 'rm', 'cp', 'mv',
                'chmod', 'chown', 'tar', 'gzip', 'gunzip', 'zip', 'unzip',
                'useradd', 'userdel', 'usermod', 'groupadd', 'groupdel',
                'passwd', 'chsh', 'chfn', 'quota', 'repquota'
            ],
            'admin' => ['*'] // All commands allowed
        ];
    }

    public function onOpen(ConnectionInterface $conn)
    {
        $this->clients->attach($conn);
        
        // Start a new shell process for this connection
        $process = new Process(['/bin/bash']);
        $process->setTty(true);
        $process->start();
        
        $this->processes[$conn->resourceId] = $process;
        
        // Handle process output
        $process->run(function ($type, $buffer) use ($conn) {
            $conn->send(json_encode([
                'type' => $type === Process::ERR ? 'error' : 'output',
                'content' => $buffer
            ]));
        });
    }

    public function onMessage(ConnectionInterface $from, $msg)
    {
        $data = json_decode($msg, true);
        
        if ($data['type'] === 'command') {
            $user = User::find($data['user_id']);
            if (!$user) {
                $from->send(json_encode([
                    'type' => 'error',
                    'content' => 'User not found'
                ]));
                return;
            }

            $role = $user->role;
            $command = $data['command'];
            
            // Check command permissions
            if (!$this->isCommandAllowed($command, $role)) {
                $from->send(json_encode([
                    'type' => 'permission_denied',
                    'content' => "Command '$command' not allowed for role '$role'"
                ]));
                return;
            }

            // Create process with user's permissions
            $process = new Process(['sudo', '-u', $data['username'], 'bash', '-c', $command]);
            $process->setTty(true);
            
            try {
                $process->run(function ($type, $buffer) use ($from) {
                    $from->send(json_encode([
                        'type' => $type === Process::ERR ? 'error' : 'output',
                        'content' => $buffer
                    ]));
                });
                
                if (!$process->isSuccessful()) {
                    throw new ProcessFailedException($process);
                }
            } catch (\Exception $e) {
                $from->send(json_encode([
                    'type' => 'error',
                    'content' => $e->getMessage()
                ]));
            }
        }
    }

    protected function isCommandAllowed($command, $role)
    {
        if (!isset($this->allowedCommands[$role])) {
            return false;
        }

        if ($this->allowedCommands[$role] === ['*']) {
            return true;
        }

        $baseCommand = explode(' ', $command)[0];
        return in_array($baseCommand, $this->allowedCommands[$role]);
    }

    public function onClose(ConnectionInterface $conn)
    {
        // Clean up the process
        if (isset($this->processes[$conn->resourceId])) {
            $this->processes[$conn->resourceId]->stop();
            unset($this->processes[$conn->resourceId]);
        }
        
        $this->clients->detach($conn);
    }

    public function onError(ConnectionInterface $conn, \Exception $e)
    {
        $conn->send(json_encode([
            'type' => 'error',
            'content' => $e->getMessage()
        ]));
        
        $conn->close();
    }
} 