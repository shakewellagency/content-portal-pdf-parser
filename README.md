# Shakewell Content Portal PDF Parser

[![Latest Version on Packagist](https://img.shields.io/packagist/v/shakewellagency/content-portal-pdf-parser.svg?style=flat-square)](https://packagist.org/packages/shakewellagency/content-portal-pdf-parser)
[![Tests](https://img.shields.io/github/actions/workflow/status/shakewellagency/content-portal-pdf-parser/run-tests.yml?branch=main&label=tests&style=flat-square)](https://github.com/shakewellagency/content-portal-pdf-parser/actions/workflows/run-tests.yml)
[![Total Downloads](https://img.shields.io/packagist/dt/shakewellagency/content-portal-pdf-parser.svg?style=flat-square)](https://packagist.org/packages/shakewellagency/content-portal-pdf-parser)

A powerful Laravel package for processing PDF documents into structured renditions with database storage, background job processing, and comprehensive email notifications. Designed for content management systems that need to extract, process, and manage PDF content at scale.

## Features

- 🔄 **Asynchronous PDF Processing** - Queue-based job system for scalable document processing
- 📊 **Database Integration** - Complete database schema with migrations for publications, packages, renditions, and assets
- 📧 **Email Notifications** - Comprehensive email system for parsing status updates
- 🗂️ **S3 Storage Integration** - Seamless AWS S3 integration for file storage and retrieval
- ⚡ **Batch Processing** - Efficient batch processing for large PDF documents (100 pages per batch)
- 🎯 **Event-Driven Architecture** - Event system for parsing lifecycle management
- 🔧 **Configurable Models** - Flexible model configuration for integration with existing applications
- 📄 **Page Extraction** - Individual page processing with asset management

## Installation

Install the package via Composer:

```bash
composer require shakewellagency/content-portal-pdf-parser
```

### Publishing Assets

Publish the package assets (migrations, enums, config, and views):

```bash
php artisan vendor:publish --tag=parser-assets
```

This will publish:
- Database migrations to `database/migrations/`
- Enums to `app/Enums/`
- Configuration file to `config/shakewell-parser.php`
- Email templates to `resources/views/mails/PDFParserMail/`

### Database Migration

Run the migrations to create the required database tables:

```bash
php artisan migrate
```

## Configuration

### Environment Variables

Add the following environment variables to your `.env` file:

```env
SHAKEWELL_PARSER_ENV=production
SHAKEWELL_PARSER_S3=s3
```

### Model Configuration

Update the published configuration file `config/shakewell-parser.php` with your application's model classes:

```php
return [
    'env' => env('SHAKEWELL_PARSER_ENV', 'develop'),
    's3' => env('SHAKEWELL_PARSER_S3', 's3'),
    'models' => [
        'publication_model' => App\Models\Publication::class,
        'version_model' => App\Models\Version::class,
        'package_model' => App\Models\Package::class,
        'rendition_model' => App\Models\Rendition::class,
        'rendition_asset_model' => App\Models\RenditionAsset::class,
        'rendition_page_model' => App\Models\RenditionPage::class,
        'toc_model' => App\Models\TableOfContents::class,
    ],
    'enums' => [
        'package_status_enum' => App\Enums\PackageStatusEnum::class,
        'publication_status_enum' => App\Enums\PublicationStatusEnum::class,
        'publication_type_enum' => App\Enums\PublicationTypeEnum::class,
        'rendition_asset_type_enum' => App\Enums\RenditionAssetTypeEnum::class,
        'rendition_type_enum' => App\Enums\RenditionTypeEnum::class,
    ],
    'timezone' => 'Australia/Sydney',
    'emails' => fn() => app('parser.emails'),
];
```

### Email Configuration

Configure the email notification system by binding the email configuration in your service provider:

```php
// In your AppServiceProvider or custom service provider
app()->bind('parser.emails', function () {
    return [
        'admin@example.com',
        'notifications@example.com',
    ];
});
```

## Usage

### Basic PDF Processing

```php
use Shakewellagency\ContentPortalPdfParser\Features\Packages\Facades\PDFParse;

// Process a PDF package
PDFParse::execute($package, $version);
```

### Processing Flow

The package follows this processing flow:

1. **Initialization** (`PackageInitializationJob`)
   - Generates package hash
   - Downloads PDF from S3
   - Counts total pages
   - Updates package status

2. **Page Processing** (`PageParserJob`)
   - Creates rendition record
   - Dispatches batch jobs for page processing

3. **Batch Processing** (`BatchParserJob`)
   - Processes pages in batches of 100
   - Extracts page content and assets
   - Stores rendition data

### Events

The package dispatches the following events during processing:

- `ParsingTriggerEvent` - When parsing is initiated
- `ParsingStartedEvent` - When processing begins
- `ParsingFinishedEvent` - When processing completes successfully
- `ParsingFailedEvent` - When processing encounters an error

### Email Notifications

Email notifications are automatically sent for each processing stage:

- **Parsing Trigger** - Notification when parsing is queued
- **Parsing Started** - Notification when processing begins
- **Parsing Finished** - Success notification with results
- **Parsing Failed** - Error notification with details

## Database Schema

The package creates the following database tables:

- `publications` - Publication metadata
- `versions` - Version information for publications
- `packages` - PDF packages and processing status
- `renditions` - Processed rendition data
- `rendition_pages` - Individual page data
- `rendition_assets` - Associated assets (images, etc.)

## Requirements

- PHP ^8.1
- Laravel ^11.9
- Queue system configured (Redis/Database/etc.)
- AWS S3 access for file storage

## Job Queue Configuration

Ensure your queue system is properly configured and running:

```bash
php artisan queue:work
```

The package uses long-running jobs (timeout: 7200 seconds) for PDF processing.

## Testing

Run the test suite:

```bash
composer test
```

Run tests with coverage:

```bash
composer test-coverage
```

## Code Style

Format code using Laravel Pint:

```bash
composer format
```

## Troubleshooting

### Common Issues

1. **Queue Not Processing**: Ensure your queue worker is running and configured properly
2. **S3 Access Issues**: Verify AWS credentials and S3 bucket permissions
3. **Memory Issues**: Large PDFs may require increased PHP memory limits
4. **Timeout Issues**: Adjust job timeout values for very large documents

### Logging

The package provides detailed logging throughout the processing pipeline. Check your Laravel logs for processing details and error information.

## Contributing

Contributions are welcome! Please ensure:

1. Tests pass: `composer test`
2. Code is formatted: `composer format`
3. Follow existing code patterns and architecture

## Security

If you discover any security vulnerabilities, please email developers@shakewell.agency instead of using the issue tracker.

## Changelog

Please see [CHANGELOG](CHANGELOG.md) for more information on recent changes.

## Credits

- [Kylyn Luterte](https://github.com/shakewellagency) - Lead Developer
- [Shakewell Agency](https://shakewell.agency) - Development Team
- [All Contributors](../../contributors)

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.
