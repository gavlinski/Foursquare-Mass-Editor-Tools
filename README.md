# Foursquare Mass Editor Tools (Elio Tools)

**Version 3.0.0** - Modernized with PHP 8.1 and Enhanced Architecture

A comprehensive collection of **Superuser Tools** for Foursquare venues mass/bulk editing and searching, powered by the Foursquare API v2. This project has been **completely modernized** with a hybrid architecture combining modern PHP 8.1 features with legacy compatibility.

## 🚀 Version 3.0.0 Highlights

- ✅ **Fully Migrated** from PHP 5.4 to PHP 8.1
- ✅ **PSR-4 Autoloading** with Composer
- ✅ **Modern Session Management** with OAuth2
- ✅ **Real-time Status Monitoring** 
- ✅ **Enhanced Security** with secure cookies and CSRF protection
- ✅ **Docker Environment** for development
- ✅ **Responsive UI** with unified status bar
- ✅ **TypeScript-ready** architecture

## 🎯 Key Features

### Import & Export
- 📄 Import full data from UTF-8 CSV files
- 🌐 Import venues from public web pages
- ✏️ Manually enter venue IDs or URLs
- 🔗 URL parameter support: `load.php?venues=id1,id2,id3`
- 📊 Export to CSV, URLs only, or editing reports

### Editing Capabilities
- ✏️ Mass editing of almost all venue fields
- 🏷️ Category management with hierarchical tree view
- 🚩 All types of venue flagging
- 📍 Google Maps integration with location markers
- 🔍 Advanced search using Foursquare API

### Modern Features (v3.0.0)
- 🔐 **OAuth2 Authentication** with automatic token refresh
- 📊 **Session Status Bar** with real-time monitoring
- 🧹 **Cache Management** with automatic cleanup
- 📱 **Responsive Design** for mobile and desktop
- 🐳 **Docker Support** for easy development
- 🔧 **Debug Tools** for development and testing

## 🏗️ Architecture

### Modern Stack
- **Backend**: PHP 8.1 with strict typing and PSR-4 autoloading
- **Frontend**: ES6 JavaScript + Dojo Toolkit v1.8.14 (hybrid approach)
- **API**: Foursquare API v2 with OAuth2 authentication
- **Infrastructure**: Docker + Apache 2.4 + Composer

### Project Structure
```
src/
├── Config/        # Modern configuration management
├── Security/      # Session and authentication handling
└── Api/           # API integration classes

js/
├── session-manager.js    # Modern ES6 session handling  
├── 4sq.js               # Core venue manipulation (legacy)
└── main.js              # Main interface logic

includes/
└── session-status-bar.php  # Unified status component
```

## 🚀 Quick Start

### Using Docker (Recommended)
```bash
# Clone the repository
git clone https://github.com/gavlinski/Foursquare-Mass-Editor-Tools.git
cd Foursquare-Mass-Editor-Tools

# Start development environment
./dev.sh

# Access the application
open http://localhost:8080
```

### Manual Setup
```bash
# Install dependencies
composer install

# Configure environment
cp .env.example .env
# Edit .env with your Foursquare API credentials

# Start PHP development server
php -S localhost:8080
```

## ⚙️ Configuration

### Environment Variables
```bash
FOURSQUARE_CLIENT_KEY="your_client_id"
FOURSQUARE_CLIENT_SECRET="your_client_secret"  
FOURSQUARE_REDIRECT_URI="http://localhost:8080/index.php"
APP_ENV="development"
APP_DEBUG="true"
```

### Foursquare API Setup
1. Create an app at [Foursquare Developers](https://developer.foursquare.com/)
2. Get your Client ID and Client Secret
3. Set redirect URI to your application URL
4. Configure environment variables

## 🔒 Requirements

### For Use
1. Foursquare user account (superuser recommended)
2. Modern web browser (Chrome 90+, Firefox 88+, Safari 14+, Edge 90+)
3. Allow application access to your Foursquare account

### For Development
- PHP 8.1+
- Composer 2.x
- Docker (optional but recommended)
- Git

## 🌍 Language Support

The interface is primarily in **Brazilian Portuguese**. For English users:
- Use your browser's translation feature
- The interface is intuitive and well-understood by English speakers
- Consider contributing English translations!

## 🛡️ Security & Best Practices

- 🔐 OAuth2 with secure token management
- 🍪 HTTPOnly and Secure cookies
- 🛡️ CSRF protection
- ✅ Input validation and sanitization
- 🔄 Automatic session regeneration
- 📝 Comprehensive error logging

## 🧪 Development & Testing

### Debug Tools
```bash
# Create test session
curl http://localhost:8080/create_test_session.php

# Check session status  
curl http://localhost:8080/session_status.php

# Clear cache
curl -X POST http://localhost:8080/clear_cache.php
```

### Testing Interface
Access `test_session_debug.html` for interactive session testing.

## 📝 Migration Notes

This version represents a **complete modernization** while maintaining **100% backward compatibility**. See [MIGRATION.md](MIGRATION.md) for detailed migration information.

### Breaking Changes
- Minimum PHP version: 8.1+
- Updated Composer dependencies
- New environment variable configuration

### New Features
- Session status monitoring
- Enhanced error handling
- Responsive design
- Docker development environment

## 🤝 Contributing

Contributions are always welcome! Please:

1. Fork the repository
2. Create a feature branch
3. Make your changes with tests
4. Submit a pull request

### Development Guidelines
- Follow PSR-4 autoloading standards
- Use strict typing for new PHP code
- Write comprehensive tests
- Update documentation

## 📄 License

This project is licensed under the [GNU General Public License v3.0](LICENSE.txt).

## 🙏 Acknowledgments

- Foursquare Labs, Inc. for their excellent API
- Dojo Toolkit community
- PHP community for modern language features
- All contributors and users

---

**⚠️ Important**: User abuses will not be tolerated. Please use this tool responsibly and follow Foursquare's terms of service.

**🌟 Star this repository** if you find it useful!

---

*Built with ❤️ by [Elio Gavlinski](https://github.com/gavlinski)*
