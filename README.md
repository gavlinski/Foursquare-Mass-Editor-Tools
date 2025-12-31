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
The easiest way to run the project. The `./dev.sh` script handles everything: dependencies (Composer & Dojo Toolkit), Docker build, and execution.

```bash
# Clone the repository
git clone https://github.com/gavlinski/Foursquare-Mass-Editor-Tools.git
cd Foursquare-Mass-Editor-Tools

# Start development environment (Builds, Installs & Runs)
./dev.sh run

# Access the application
open http://localhost/4sqmet
```

### Manual Setup
```bash
# Install dependencies
composer install

# Configure environment
cp .env.example .env
# Edit .env with your Foursquare API credentials

# Start PHP development server
php -S localhost
```

## ⚙️ Configuration

### Environment Variables
Copy `.env.example` to `.env` and fill in your credentials. This is the **single source of truth** for configuration.

```bash
cp .env.example .env
```

Required variables in `.env`:
```bash
# Foursquare API
FOURSQUARE_CLIENT_KEY="your_client_id"
FOURSQUARE_CLIENT_SECRET="your_client_secret"  
FOURSQUARE_REDIRECT_URI="http://localhost/4sqmet/index.php"

# Google Maps API
GOOGLE_MAPS_API_KEY="your_google_maps_key"
GOOGLE_MAPS_MAP_ID="your_map_id"

# App Settings
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
curl http://localhost/create_test_session.php

# Check session status  
curl http://localhost/session_status.php

# Clear cache
curl -X POST http://localhost/clear_cache.php
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

## 🔧 Testing and Debugging

### Debug Tools (debug/ folder)

The project includes consolidated testing tools for development:

- **`test_session_debug.html`** - Interactive web interface for session testing
- **`session_test_manager.php`** - Consolidated session management API
  - Create/destroy test sessions
  - Multiple output formats (JSON/simple)
  - Usage: `?action=create|destroy|status&format=json|simple`
- **`debug_session.php`** - Session validation and debug information
  - Validate current sessions
  - Simulate complete sessions
  - Full debug mode with server info
  - Usage: `?mode=validate|simulate|debug`
- **`css_test_interface.php`** - CSS and styling test interface
- **`legacy_session_creator.php`** - Legacy session creation script

### Quick Testing Commands

```bash
# Access debug interface
open http://localhost/debug/test_session_debug.html

# Test session creation via API
curl "http://localhost/debug/session_test_manager.php?action=create"

# Validate current session
curl "http://localhost/debug/debug_session.php?mode=validate"

# Get full debug info
curl "http://localhost/debug/debug_session.php?mode=debug"
```

### Migration Status

⚠️ **Migration in Progress**: The codebase is currently being modernized from PHP 5.4 to PHP 8.1 with Google Maps API integration. Some features may be in transition.

**Completed**:

- ✅ Modern Google Maps integration (`js/modern-google-maps.js`)
- ✅ Consolidated debug tools
- ✅ Docker containerization
- ✅ Session management modernization

**In Progress**:

- 🔄 Complete Google Maps legacy code removal
- 🔄 Full PSR-4 code migration
- 🔄 Comprehensive testing coverage

**Testing Recommendations**:

1. Use debug tools before making changes
2. Test session functionality after code modifications
3. Verify Google Maps integration works correctly
4. Check both local and containerized environments

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
