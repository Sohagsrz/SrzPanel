class WebSocketClient {
    constructor(url, token) {
        this.url = url;
        this.token = token;
        this.ws = null;
        this.connected = false;
        this.reconnectAttempts = 0;
        this.maxReconnectAttempts = 5;
        this.reconnectDelay = 1000;
        this.handlers = {
            'open': [],
            'close': [],
            'error': [],
            'message': [],
            'auth': [],
            'terminal': []
        };
    }

    connect() {
        try {
            this.ws = new WebSocket(this.url);
            this.setupEventHandlers();
        } catch (error) {
            this.handleError(error);
        }
    }

    setupEventHandlers() {
        this.ws.onopen = () => {
            this.connected = true;
            this.reconnectAttempts = 0;
            this.authenticate();
            this.trigger('open');
        };

        this.ws.onclose = () => {
            this.connected = false;
            this.trigger('close');
            this.reconnect();
        };

        this.ws.onerror = (error) => {
            this.handleError(error);
        };

        this.ws.onmessage = (event) => {
            try {
                const data = JSON.parse(event.data);
                this.trigger('message', data);
                
                if (data.type) {
                    this.trigger(data.type, data);
                }
            } catch (error) {
                console.error('Failed to parse message:', error);
            }
        };
    }

    authenticate() {
        this.send({
            type: 'auth',
            token: this.token
        });
    }

    sendTerminalCommand(command) {
        this.send({
            type: 'terminal',
            command: command
        });
    }

    send(data) {
        if (!this.connected) {
            throw new Error('WebSocket is not connected');
        }
        this.ws.send(JSON.stringify(data));
    }

    reconnect() {
        if (this.reconnectAttempts < this.maxReconnectAttempts) {
            this.reconnectAttempts++;
            setTimeout(() => {
                console.log(`Reconnecting... Attempt ${this.reconnectAttempts}`);
                this.connect();
            }, this.reconnectDelay * this.reconnectAttempts);
        } else {
            this.trigger('error', new Error('Max reconnection attempts reached'));
        }
    }

    handleError(error) {
        console.error('WebSocket error:', error);
        this.trigger('error', error);
    }

    on(event, handler) {
        if (this.handlers[event]) {
            this.handlers[event].push(handler);
        }
        return this;
    }

    trigger(event, data) {
        if (this.handlers[event]) {
            this.handlers[event].forEach(handler => handler(data));
        }
    }

    disconnect() {
        if (this.ws) {
            this.ws.close();
        }
    }
}

// Example usage:
/*
const ws = new WebSocketClient('ws://your-server:8080', 'your-auth-token');

ws.on('open', () => {
    console.log('Connected to WebSocket server');
});

ws.on('auth', (data) => {
    if (data.status === 'success') {
        console.log('Authenticated as:', data.user.name);
    }
});

ws.on('terminal', (data) => {
    if (data.type === 'output') {
        console.log('Terminal output:', data.content);
    } else if (data.type === 'error') {
        console.error('Terminal error:', data.content);
    }
});

ws.on('error', (error) => {
    console.error('WebSocket error:', error);
});

ws.connect();

// Send a terminal command
ws.sendTerminalCommand('ls -la');
*/ 