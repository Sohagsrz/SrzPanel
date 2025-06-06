# SrzPanel - Modern Hosting Management Panel

[![License: MIT](https://img.shields.io/badge/License-MIT-yellow.svg)](https://opensource.org/licenses/MIT)
[![Laravel](https://img.shields.io/badge/Laravel-10.x-red.svg)](https://laravel.com)
[![PHP](https://img.shields.io/badge/PHP-8.1+-blue.svg)](https://php.net)

SrzPanel is an open-source, modern hosting management panel built with Laravel. It provides a comprehensive solution for managing web hosting services, similar to cPanel but with a modern interface and enhanced features.

## 🌟 Features

### User Management

-   Multi-level user system (Admin → Reseller → User)
-   Role-based access control
-   User suspension capability
-   Activity logging
-   Two-factor authentication (2FA)

### Server Management

-   Real-time server monitoring
-   Resource usage tracking
-   System updates management
-   Security monitoring
-   WebSocket integration for live updates

### Domain Management

-   DNS management
-   SSL certificate handling
-   Domain templates
-   DNS record management
-   Domain transfer functionality

### Email System

-   Email account management
-   Custom email templates
-   Email quotas
-   Spam protection
-   Blacklist/whitelist management

### Database Management

-   Database creation/deletion
-   User management
-   Backup/restore functionality
-   Performance monitoring
-   Query optimization tools

### Backup System

-   Automated backups
-   Backup scheduling
-   Restore functionality
-   Storage management
-   Backup encryption

### Security Features

-   Role-based permissions
-   API token authentication
-   IP allowlisting
-   Rate limiting
-   Webhook security
-   Firewall management
-   SSL certificate auto-renewal

### API Integration

-   RESTful API
-   Webhook support
-   Token-based authentication
-   Rate limiting
-   IP restrictions

## 🚀 Quick Start

### Prerequisites

-   PHP 8.1 or higher
-   Composer
-   Node.js & NPM
-   MySQL/MariaDB
-   Redis (optional, for caching)

### Installation

1. Clone the repository:

```bash
git clone https://github.com/Sohagsrz/SrzPanel.git
cd SrzPanel
```

2. Install PHP dependencies:

```bash
composer install
```

3. Install NPM dependencies:

```bash
npm install
```

4. Create environment file:

```bash
cp .env.example .env
```

5. Generate application key:

```bash
php artisan key:generate
```

6. Configure your database in `.env`:

```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=srzpanel
DB_USERNAME=your_username
DB_PASSWORD=your_password
```

7. Run migrations and seeders:

```bash
php artisan migrate --seed
```

8. Build assets:

```bash
npm run build
```

9. Start the development server:

```bash
php artisan serve
```

Visit `http://localhost:8000` to access the panel.

## 🔧 Configuration

### WebSocket Server

Configure WebSocket server in `.env`:

```env
WEBSOCKET_HOST=127.0.0.1
WEBSOCKET_PORT=6001
```

### Queue Worker

Start the queue worker for background jobs:

```bash
php artisan queue:work
```

### SSL Configuration

Configure SSL settings in `.env`:

```env
SSL_AUTO_RENEW=true
SSL_RENEW_DAYS=30
```

## 📚 Documentation

Detailed documentation is available in the [docs](docs) directory:

-   [Installation Guide](docs/installation.md)
-   [User Management](docs/user-management.md)
-   [Server Management](docs/server-management.md)
-   [API Documentation](docs/api.md)
-   [Security Guide](docs/security.md)

## 🤝 Contributing

We welcome contributions! Please see our [Contributing Guide](CONTRIBUTING.md) for details.

1. Fork the repository
2. Create your feature branch (`git checkout -b feature/amazing-feature`)
3. Commit your changes (`git commit -m 'Add some amazing feature'`)
4. Push to the branch (`git push origin feature/amazing-feature`)
5. Open a Pull Request

## 🐛 Bug Reports

If you discover any bugs, please create an issue in the GitHub repository.

## 🔒 Security

If you discover any security-related issues, please email security@srzpanel.com instead of using the issue tracker.

## 📄 License

This project is open-sourced software licensed under the [MIT license](https://opensource.org/licenses/MIT).

## 🙏 Acknowledgments

-   [Laravel](https://laravel.com)
-   [Livewire](https://livewire.laravel.com)
-   [Spatie Permission](https://github.com/spatie/laravel-permission)
-   [Tailwind CSS](https://tailwindcss.com)

## 🌟 Support

If you like this project, please give it a ⭐️ on GitHub!

## 📞 Contact

-   Website: [https://srzpanel.com](https://srzpanel.com)
-   Email: support@srzpanel.com
-   Twitter: [@SrzPanel](https://twitter.com/SrzPanel)

---

Built with ❤️ by [Sohag](https://github.com/Sohagsrz)
