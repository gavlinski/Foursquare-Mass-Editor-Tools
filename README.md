# Foursquare Mass Editor Tools (Elio Tools)

**Version 3.1.0** - Analytics Dashboard, Dojo CDN & Production Docker

A comprehensive collection of **Superuser Tools** for Foursquare venues mass/bulk editing and searching, powered by the Foursquare API v2. This project has been **completely modernized** with a hybrid architecture combining modern PHP 8.1 features with legacy compatibility.

## 🚀 Version 3.1.0 Highlights

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

### Modern Features (v3.1.0)
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

Two development environment options are available. Both can coexist — but **never run them simultaneously** (port conflict on 80/443).

### 🆕 VS Code Dev Container (Recommended)

Opens the project **inside** the Docker container. PHP IntelliSense, npm, and Apache are pre-configured.
Requires VS Code + **Dev Containers** extension + OrbStack or Docker Desktop.

```bash
# Clone the repository
git clone https://github.com/gavlinski/Foursquare-Mass-Editor-Tools.git
cd Foursquare-Mass-Editor-Tools

# Open in VS Code and run:
# Cmd+Shift+P → "Dev Containers: Reopen in Container"
```

After setup: `https://localhost/4sqmet/` — edits reflect in the browser immediately.

> 📖 **Full guide**: [docs/DEV_CONTAINER.md](docs/DEV_CONTAINER.md) — step-by-step setup, OrbStack vs Docker Desktop, available scripts, and troubleshooting.

### External Docker (`./dev.sh`)

Manages the container from the host machine terminal.

```bash
# Clone the repository
git clone https://github.com/gavlinski/Foursquare-Mass-Editor-Tools.git
cd Foursquare-Mass-Editor-Tools

# Start container (build + install + run)
./dev.sh run

# Access the application
open https://localhost/4sqmet
```

> **📍 URL Differences by Environment:**
> - **Development**: `https://localhost/4sqmet/` (sub-directory with HTTPS)
> - **Production**: `https://4sq.eliotools.site/` (root domain)
> 
> OAuth Redirect URIs must match:
> - Dev: `https://localhost/4sqmet/index.php`
> - Prod: `https://4sq.eliotools.site/index.php`

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
FOURSQUARE_REDIRECT_URI="https://localhost/4sqmet/index.php"

# Google Maps API
GOOGLE_MAPS_API_KEY="your_google_maps_key"
GOOGLE_MAPS_MAP_ID="your_map_id"

# App Settings
APP_ENV="development"
APP_DEBUG="true"

# Dojo Loading Strategy
DOJO_SOURCE="cdn"
DOJO_FORCE_FALLBACK="false"
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
curl -k "https://localhost/debug/session_test_manager.php?action=create"

# Validate current session
curl -k "https://localhost/debug/debug_session.php?mode=validate"

# Clear cache
curl -k -X POST https://localhost/clear_cache.php
```

### Testing Interface
Access `debug/test_session_debug.html` for interactive session testing.

For Dojo source/fallback diagnostics, use `debug/test_dojo_cdn.php`.

## 📝 Migration Notes

This version represents a **hybrid modernization** while maintaining backward compatibility in core workflows. See [docs/migration/MIGRATION.md](docs/migration/MIGRATION.md) for detailed migration information.

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

## 📝 Changelog

### Version 3.1.0 (2026-03-16)

**Analytics Dashboard:**
- Privacy-friendly self-hosted analytics (no external services)
- Dashboard with unique visitors, page views, and top pages
- Data migration script for legacy analytics cleanup

**Infrastructure & Deployment:**
- Production migration to new Docker Droplet (NYC3, Ubuntu 22.04)
- Let's Encrypt SSL with automatic renewal
- DigitalOcean Monitoring: Resource Alerts + Uptime Check
- Dojo Toolkit migrated to Google CDN (primary) with local fallback (`js/dojo`, `js/dijit`, `js/dojox`)
- Git tags realigned with SemVer (4SQMET-03_xx_xx series)

**Build System:**
- Loading overlay to prevent FOUC during Dojo initialization
- Geocoding API server-side support
- GitHub Copilot agent skills and instructions

### Version 3.0.1 (2026-02-18)

**Session Management Improvements:**
- Reduced session check interval from 5 minutes to 2 minutes for faster expiration detection
- Implemented cache busting in session status requests to prevent stale data
- Enhanced session expiration detection logic (checks both `status='expired'` and `!authenticated`)
- Added visibility change and focus event listeners for immediate session verification
- Implemented cross-tab logout detection via localStorage events
- Session timer now properly pauses on error/expired states

**OAuth & Authentication:**
- Added protection against OAuth redirect loops (max 10 attempts)
- Implemented token validation before accepting cookie-stored tokens
- Improved token restoration priority: session → cookie → superglobal
- Added comprehensive debug logging for OAuth flow tracking

**User Experience Enhancements:**
- File upload state now persists via cookies when navigating back
- Intelligent navigation detection (F5 vs Back button) using Performance API
- Automatic file state restoration on browser back navigation
- Enhanced venue editing with proper Dijit widget updates
- Fixed placeholder visibility after loading venue data
- Added `habilitarCamposLinha()` function to properly re-enable fields

**UI & Styling:**
- Fixed category tree positioning with proper CSS float clearing
- Reduced session bar padding on large screens (40px → 15px)
- Improved visual feedback with automatic error class removal on success
- Session bar timer now respects `showTimer` parameter for better state control

**Validation & Error Handling:**
- Enhanced search coordinate validation with empty field checking
- Better geocoding success verification before accessing results
- Filtered error listeners to show only session-related errors
- Improved error messages and user feedback

### Version 3.0.0 (2025-07)
- Initial modernization release with PHP 8.1 migration (tag: 4SQMET-03_00_00)
- PSR-4 autoloading implementation
- Docker containerization
- Modern session management system

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
open https://localhost/debug/test_session_debug.html

# Test session creation via API
curl -k "https://localhost/debug/session_test_manager.php?action=create"

# Validate current session
curl -k "https://localhost/debug/debug_session.php?mode=validate"

# Get full debug info
curl -k "https://localhost/debug/debug_session.php?mode=debug"
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
