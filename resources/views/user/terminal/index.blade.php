@extends('layouts.app')

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">User Terminal</h3>
                    <div class="card-tools">
                        <button type="button" class="btn btn-tool" data-card-widget="collapse">
                            <i class="fas fa-minus"></i>
                        </button>
                        <button type="button" class="btn btn-tool" data-card-widget="maximize">
                            <i class="fas fa-expand"></i>
                        </button>
                    </div>
                </div>
                <div class="card-body">
                    <div class="terminal-container">
                        <div id="terminal" class="terminal"></div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

@push('styles')
<style>
.terminal-container {
    background-color: #1e1e1e;
    border-radius: 4px;
    padding: 10px;
    height: 600px;
    overflow: hidden;
}

.terminal {
    font-family: 'Courier New', monospace;
    color: #fff;
    height: 100%;
    overflow-y: auto;
}

.terminal .line {
    margin: 0;
    padding: 2px 0;
    white-space: pre-wrap;
    word-wrap: break-word;
}

.terminal .prompt {
    color: #0f0;
}

.terminal .command {
    color: #fff;
}

.terminal .output {
    color: #ccc;
}

.terminal .error {
    color: #f00;
}

.terminal .success {
    color: #0f0;
}

.terminal .warning {
    color: #ff0;
}

.terminal .info {
    color: #0ff;
}
</style>
@endpush

@push('scripts')
<script src="https://cdnjs.cloudflare.com/ajax/libs/xterm/3.14.5/xterm.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/xterm/3.14.5/addons/fit/fit.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    const terminal = new Terminal({
        cursorBlink: true,
        fontSize: 14,
        fontFamily: 'Courier New, monospace',
        theme: {
            background: '#1e1e1e',
            foreground: '#fff'
        }
    });

    terminal.open(document.getElementById('terminal'));
    Terminal.applyAddon(fit);

    // Connect to WebSocket
    const ws = new WebSocket('{{ config('app.ws_url') }}/terminal');
    let currentCommand = '';
    let commandHistory = [];
    let historyIndex = -1;

    ws.onopen = function() {
        terminal.write('\r\n\x1B[1;32mConnected to user terminal\x1B[0m\r\n');
        terminal.write('\r\n\x1B[1;32m{{ auth()->user()->username }}@server:~$ \x1B[0m');
    };

    ws.onmessage = function(event) {
        const data = JSON.parse(event.data);
        if (data.type === 'output') {
            terminal.write(data.content);
        } else if (data.type === 'error') {
            terminal.write('\r\n\x1B[1;31m' + data.content + '\x1B[0m\r\n');
        } else if (data.type === 'permission_denied') {
            terminal.write('\r\n\x1B[1;31mPermission denied: ' + data.content + '\x1B[0m\r\n');
        }
        terminal.write('\r\n\x1B[1;32m{{ auth()->user()->username }}@server:~$ \x1B[0m');
    };

    ws.onclose = function() {
        terminal.write('\r\n\x1B[1;31mConnection closed\x1B[0m\r\n');
    };

    terminal.onKey(({ key, domEvent }) => {
        const printable = !domEvent.altKey && !domEvent.ctrlKey && !domEvent.metaKey;

        if (domEvent.keyCode === 13) { // Enter
            terminal.write('\r\n');
            if (currentCommand.trim()) {
                commandHistory.push(currentCommand);
                historyIndex = commandHistory.length;
                ws.send(JSON.stringify({
                    type: 'command',
                    command: currentCommand,
                    user_id: {{ auth()->id() }},
                    username: '{{ auth()->user()->username }}'
                }));
            }
            currentCommand = '';
            terminal.write('\x1B[1;32m{{ auth()->user()->username }}@server:~$ \x1B[0m');
        } else if (domEvent.keyCode === 8) { // Backspace
            if (currentCommand.length > 0) {
                currentCommand = currentCommand.slice(0, -1);
                terminal.write('\b \b');
            }
        } else if (domEvent.keyCode === 38) { // Up arrow
            if (historyIndex > 0) {
                historyIndex--;
                currentCommand = commandHistory[historyIndex];
                terminal.write('\r\x1B[1;32m{{ auth()->user()->username }}@server:~$ \x1B[0m' + currentCommand);
            }
        } else if (domEvent.keyCode === 40) { // Down arrow
            if (historyIndex < commandHistory.length - 1) {
                historyIndex++;
                currentCommand = commandHistory[historyIndex];
                terminal.write('\r\x1B[1;32m{{ auth()->user()->username }}@server:~$ \x1B[0m' + currentCommand);
            } else {
                historyIndex = commandHistory.length;
                currentCommand = '';
                terminal.write('\r\x1B[1;32m{{ auth()->user()->username }}@server:~$ \x1B[0m');
            }
        } else if (printable) {
            currentCommand += key;
            terminal.write(key);
        }
    });

    // Handle terminal resize
    function fitTerminal() {
        terminal.fit();
    }

    window.addEventListener('resize', fitTerminal);
    fitTerminal();

    // Focus terminal on click
    document.getElementById('terminal').addEventListener('click', function() {
        terminal.focus();
    });
});
</script>
@endpush
@endsection 