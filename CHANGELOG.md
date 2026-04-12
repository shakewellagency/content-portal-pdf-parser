# Changelog

All notable changes to `content-portal-pdf-parser` will be documented in this file.

## 1.0.5 - 2026-04-12

### Added
- `ResolvesLinearizedFilePath` trait for the host `package_model`, transparently resolving `file_path` reads to the linearized object key when set.
- Laravel 13 support (`laravel/framework: ^11.9|^12.0|^13.0`).
- Pest test suite with `orchestra/testbench`: coverage for `ResolvesLinearizedFilePath`, `QpdfLinearizeService`, and `LinearizePackagePdfJob` guard clauses, success path, and temp-file cleanup on failure.

### Fixed
- `LinearizePackagePdfJob` was leaking empty temp files on every run because `tempnam()` return values were used after appending `.pdf`. Temp files are now used as created and cleaned up correctly.
- `LinearizePackagePdfJob` no longer writes to the undeclared `pdf_viewer_v2` column; only `linearized_file_path` is persisted.

### Changed
- `LinearizePackagePdfJob` retries once on transient failures (`$tries = 2`, `$backoff = 60`).

## 1.0.4 - 2026-04-12

### Added
- Optional PDF linearization (Fast Web View): `LinearizePackagePdfJob` and `QpdfLinearizeService` (requires `qpdf` on queue workers), dispatched from `PDFParse::execute` when `linearize_on_parse` is enabled.
- `linearized_file_path` column migration under `databases/migrations/package_updates`, auto-loaded via `ParserServiceProvider::loadMigrationsFrom`.
- `ResolvesLinearizedFilePath` trait for the host `package_model` so `file_path` resolves to the linearized object key when set.
- Config keys: `linearize_on_parse`, `qpdf_binary`, `qpdf_timeout_seconds`; dependency `symfony/process`.

## 1.0.2 - 2025-10-31

### Fixed
- Fixed PDF page splitting issue where all pages contained the entire PDF instead of individual pages
- Updated glob pattern in PDFPageParserAction to match only files for the specific page being processed
- Added page-specific prefix filtering to prevent cross-page file contamination
- Added detailed logging to track file processing for debugging purposes

## 1.0.0 - 2025-01-09

### Added
- Initial stable release of the Content Portal PDF Parser
- Asynchronous PDF processing with Laravel queue system
- Complete database schema with migrations for publications, packages, renditions, and assets  
- Event-driven architecture with comprehensive email notifications
- S3 storage integration for file management
- Batch processing system for large PDF documents (100 pages per batch)
- Configurable model and enum system for flexible integration
- Individual page extraction and asset management
- Comprehensive email notification system for all parsing stages
- Long-running job support with 7200-second timeout
- Package initialization with hash generation and page counting
- Rendition creation and management system

### Features
- `PDFParse::execute()` facade for easy PDF processing
- `PackageInitializationJob` for PDF setup and validation
- `PageParserJob` for rendition creation and batch job dispatch
- `BatchParserJob` for efficient page processing
- Event listeners for parsing lifecycle notifications
- Database tables: publications, versions, packages, renditions, rendition_pages, rendition_assets
- Email templates for parsing trigger, started, finished, and failed states
- Configuration file for model and enum customization
- Service provider with automatic asset publishing

### Documentation
- Comprehensive README with installation and configuration guide
- Usage examples and processing flow documentation
- Database schema overview and requirements
- Troubleshooting guide for common issues
- Complete API documentation for all major components

