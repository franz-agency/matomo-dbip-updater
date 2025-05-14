# Changelog

## 1.4.0 - 2025-05-14
### Changed
- Project restructured: moved all plugin files to DbipUpdater/ subdirectory
- Kept development files in root directory for better development experience
- Updated author name from "Franz & Franz" to "Franz und Franz"
- Added development tools with linting for PHP and Markdown
- Added comprehensive development documentation
- Fixed coding style issues according to PSR-12 standard

## 1.3.0 - 2025-05-14
### Added
- Added robust retry mechanism for failed connections
- Added detailed logging functionality for troubleshooting
- Added configurable connection timeouts and retry settings
- Added URL validation and better error reporting
- Added comprehensive documentation with installation and usage instructions
- Added proper author attribution in code comments

### Fixed
- Fixed settings access by using the correct scope (PLUGIN_SCOPE instead of USER_SCOPE)
- Fixed error handling with more descriptive messages
- Improved HTTP request handling with timeout detection

### Changed
- Refactored UpdateMmdbUrl task for better maintainability
- Enhanced settings with more configuration options
- Improved installation and lifecycle hooks

## 1.2.0 - Previous version
- Initial public release