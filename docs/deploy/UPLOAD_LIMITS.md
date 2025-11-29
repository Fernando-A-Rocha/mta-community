# Upload File Size Limits Configuration

This document outlines the maximum upload file sizes configured across the application stack and how they align to ensure consistent file upload behavior.

## Overview

The application allows users to upload resource ZIP files and associated images. The upload limits are enforced at multiple layers:

1. **Laravel Validation** - Application-level validation rules
2. **Nginx** - Web server request body size limit
3. **PHP** - PHP-FPM upload and post size limits

All three layers must be configured consistently to prevent upload failures.

## Current Limits

### Laravel Validation Rules

Defined in comm2/app/Http/Requests for Images, ZIP Files.

### Nginx Configuration

Configured in `deploy/nginx.conf`:

```nginx
client_max_body_size 25M;
```

The nginx limit must be slightly larger than the Laravel validation limit to account for:

- HTTP headers and metadata
- Multipart form data overhead
- Request body encoding overhead

### PHP-FPM Configuration

PHP-FPM must be configured with the following settings (usually `/etc/php/8.4/fpm/php.ini`, not the CLI php.ini):

```ini
upload_max_filesize = 25M
post_max_size = 25M
memory_limit = 256M
```

## Alignment Requirements

For uploads to work correctly, the limits must follow this hierarchy:

```
nginx client_max_body_size (25M)
    ≥
PHP post_max_size (25M)
    ≥
PHP upload_max_filesize (25M)
    ≥
Laravel validation max (20MB)
```

**Critical:** If any layer has a limit lower than the Laravel validation, uploads will fail with:

- **Nginx**: `413 Request Entity Too Large`
- **PHP-FPM**: Silent failure or `POST Content-Length` errors
- **Laravel**: Validation error (expected behavior)

## Verification

### Check Nginx Configuration

```bash
nginx -t  # Test configuration syntax
nginx -T | grep client_max_body_size  # Verify setting
```

## Changing Upload Limits

If you need to change the upload limits:

1. **Update Laravel validation**
2. **Update nginx** `client_max_body_size` (add ~5MB overhead)
3. **Update PHP-FPM** `upload_max_filesize` and `post_max_size` to match nginx
4. **Update this documentation**
5. **Test thoroughly** before deploying to production
