# Changelog

## Version 3.3.2 (2026-10-13)

**Deploy and Security:**
- Fixed tag deployment by allowing the fetched tag reference to be updated locally.
- Added Copilot hooks for command safety warnings and commit secret scanning, with automated tests.

**Chains Search:**
- Required Placemaker level 9 for the Chains search API and updated the related regression coverage.

## Version 3.3.1 (2026-10-13)

**Hotfixes:**
- Improved chain search API error handling and refreshed tooltips after chain updates.
- Refined map interaction by preventing map icons from capturing clicks and layering the resize handle correctly.
- Updated chain and CSV E2E coverage for the current editing function parameters.
- Removed an unsupported Playwright MCP configuration option.

## Version 3.3.0 (2026-10-12)

**Chains:**
- Added the Chains field to venue loading and editing workflows, with chain search, direct ID entry, logos, admin links, and primary-chain selection.
- Added and verified API v2 proposals for adding, removing, and changing a venue's primary chain. These edit parameters are not documented in the public v2 API reference.
- Chain loading and display now follow the user's field selection, including after reloading partial results as complete venue data.
- CSV exports conditionally include chain IDs and edit operations; partial, complete, and reloaded rows remain column-aligned.
- Improved chain editing feedback, request debouncing/cancellation, and handling of chain names and logos.

**Editing and Reliability:**
- Bulk edits now target selected venues and prefill a value when all targeted venues share it.
- Improved category search to match substrings and traverse nested categories; refined category and chain selection feedback.
- Isolated synthetic Playwright sessions from real authentication state, invalidated synthetic tokens, and disabled session test utilities in production.
- Added E2E coverage for chain selection and a CSV consistency harness for partial, complete, and reloaded venues.

## Version 3.2.1 (2026-09-13)

**Venue URLs & Export:**
- Added support for shared venue URLs using the `/share/venue/{id}` format.
- Canonical URL export now generates shareable URLs from venue IDs.
- Standard URL and ID exports now use the venue ID directly.
- Ignored unreliable `canonicalUrl` values that could rewrite links to `/share/venue/`.

**Configuration & Links:**
- Editor links now use the configured Foursquare client key when available.
- Empty `?ref=` parameters are omitted when no client key is configured.

## Version 3.2.0 (2026-03-31)

**Deploy & CI/CD:**
- Improved deploy flow safety: git stash handling for dirty working tree on server.
- Apache ServerName global written at container startup (eliminates AH00558 warning).
- CI/CD workflow refined: Dojo local fallback validation step added.
- Tag convention migrated to SemVer `v*` format for GitHub ecosystem alignment.

**Authentication & Security:**
- Cookie handling hardened for reliability across sessions.
- Auth flow edge cases resolved.

**Data & UI:**
- CSV: expired file handling improvements.
- Postal code field: paste support enabled.
- Local Dojo/Dijit/Dojox fallback assets included in repository.

**Documentation:**
- BUILD_AND_DEPLOY.md updated with deploy scenarios, build_source field details.
- GitHub Copilot agent skills expanded (ci-cd, deploy-safety, devcontainer).
- Dependabot configuration added for automated dependency updates.

## Version 3.1.0 (2026-03-16)

**Analytics Dashboard:**
- Privacy-friendly self-hosted analytics (no external services).
- Dashboard with unique visitors, page views, and top pages.
- Data migration script for legacy analytics cleanup.

**Infrastructure & Deployment:**
- Production migration to new Docker Droplet (NYC3, Ubuntu 22.04).
- Let's Encrypt SSL with automatic renewal.
- DigitalOcean Monitoring: Resource Alerts + Uptime Check.
- Dojo Toolkit migrated to Google CDN (primary) with local fallback (`js/dojo`, `js/dijit`, `js/dojox`).
- Git tags realigned with SemVer (4SQMET-03_xx_xx series).

**Build System:**
- Loading overlay to prevent FOUC during Dojo initialization.
- Geocoding API server-side support.
- GitHub Copilot agent skills and instructions.

## Version 3.0.1 (2026-02-18)

**Session Management Improvements:**
- Reduced session check interval from 5 minutes to 2 minutes for faster expiration detection.
- Implemented cache busting in session status requests to prevent stale data.
- Enhanced session expiration detection logic (checks both `status='expired'` and `!authenticated`).
- Added visibility change and focus event listeners for immediate session verification.
- Implemented cross-tab logout detection via localStorage events.
- Session timer now properly pauses on error/expired states.

**OAuth & Authentication:**
- Added protection against OAuth redirect loops (max 10 attempts).
- Implemented token validation before accepting cookie-stored tokens.
- Improved token restoration priority: session → cookie → superglobal.
- Added comprehensive debug logging for OAuth flow tracking.

**User Experience Enhancements:**
- File upload state now persists via cookies when navigating back.
- Intelligent navigation detection (F5 vs Back button) using Performance API.
- Automatic file state restoration on browser back navigation.
- Enhanced venue editing with proper Dijit widget updates.
- Fixed placeholder visibility after loading venue data.
- Added `habilitarCamposLinha()` function to properly re-enable fields.

**UI & Styling:**
- Fixed category tree positioning with proper CSS float clearing.
- Reduced session bar padding on large screens (40px → 15px).
- Improved visual feedback with automatic error class removal on success.
- Session bar timer now respects `showTimer` parameter for better state control.

**Validation & Error Handling:**
- Enhanced search coordinate validation with empty field checking.
- Better geocoding success verification before accessing results.
- Filtered error listeners to show only session-related errors.
- Improved error messages and user feedback.

## Version 3.0.0 (2025-07)

- Initial modernization release with PHP 8.1 migration (tag: 4SQMET-03_00_00).
- PSR-4 autoloading implementation.
- Docker containerization.