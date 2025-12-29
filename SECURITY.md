# Security Documentation

This document outlines the security measures implemented in this PHP API and best practices for secure deployment.

## Security Fixes Implemented

### 1. IP Spoofing Prevention (CVE-like: Header Injection)
**Issue**: The API trusted `X-Forwarded-For` headers without validation, allowing attackers to spoof their IP address and bypass rate limiting.

**Fix**: 
- Changed `userIP()` function to prioritize `REMOTE_ADDR` (harder to spoof)
- Added `TRUST_PROXY` setting (default: `false`) - only enable behind trusted reverse proxies
- Added IP validation with `filter_var()` when `TRUST_PROXY` is enabled
- Added `ALLOW_PRIVATE_IPS` setting to control whether private IP ranges are accepted

**Configuration**:
```php
"TRUST_PROXY" => false,        // Set to true only if behind nginx/Apache reverse proxy
"ALLOW_PRIVATE_IPS" => true,   // Set to false to block RFC1918 private IPs
```

### 2. Path Traversal (CWE-22)
**Issue**: User-controlled folder names in `api_includes.php` could lead to directory traversal attacks.

**Fix**:
- Added regex validation for folder names (alphanumeric, underscore, hyphen only)
- Added `realpath()` checks to ensure files are in expected directories
- Improved redirect URL validation to only allow simple filenames without paths

### 3. Open Redirect (CWE-601)
**Issue**: `CUSTOM_INDEX` and `CUSTOM_INDEX_NOPARAMS` values used in redirects without validation.

**Fix**:
- Validate redirect URLs to only allow simple PHP filenames
- Use `basename()` to strip any path components
- Added pattern matching: `^[a-zA-Z0-9_\-]+\.php$`
- HTML entity encoding of redirect URLs

### 4. Race Conditions (CWE-362)
**Issue**: Log files and JSON files opened with 'w+' mode without locking, causing potential data corruption.

**Fix**:
- Implemented `flock()` with `LOCK_EX` for all write operations
- Implemented `flock()` with `LOCK_SH` for all read operations
- Proper lock release after operations

### 5. Timing Attacks (CWE-208)
**Issue**: API key comparison using `==` operator allows timing analysis attacks.

**Fix**:
- Replaced `==` with `hash_equals()` for constant-time comparison
- Prevents attackers from determining correct keys through timing measurements

### 6. Log Injection (CWE-117)
**Issue**: Unsanitized user input written to log files allows forging of fake log entries.

**Fix**:
- Strip newlines (`\n`), carriage returns (`\r`), and null bytes (`\0`) from log messages
- Prevents injection of fake log entries

### 7. Input Validation (CWE-20)
**Issue**: Endpoint parameter not validated before use in function calls.

**Fix**:
- Added regex validation: `^[a-zA-Z0-9_]+$`
- Only alphanumeric characters and underscores allowed
- Prevents potential code injection via endpoint names

### 8. Information Disclosure (CWE-200)
**Issue**: Detailed error messages expose internal structure and file paths.

**Fix**:
- Added `PRODUCTION_MODE` setting (default: `false`)
- When enabled, generic error messages returned for 5xx errors
- Detailed errors still logged for debugging
- HTML entity encoding for all error output

### 9. JSON Validation (CWE-755)
**Issue**: No validation of JSON decode results, potential for corrupted data.

**Fix**:
- Created `validate_json_decode()` helper function
- Checks `json_last_error()` for decode failures
- Proper error handling for malformed JSON

### 10. CORS Misconfiguration (CWE-942)
**Issue**: Hardcoded `Access-Control-Allow-Origin: *` allows all origins.

**Fix**:
- Added `CORS_ALLOW_ORIGIN` setting (default: `*` for backward compatibility)
- Configurable per-deployment
- Can be restricted to specific domains

## Security Settings Reference

### Required Settings for Production

```php
// In settings/my_custom_settings.php

const PRODUCTION_MODE = true;           // Reduce information disclosure
const TRUST_PROXY = false;              // Only enable if behind trusted proxy
const CORS_ALLOW_ORIGIN = "https://yourdomain.com";  // Restrict to your domain
```

### Optional Security Settings

```php
const ALLOW_PRIVATE_IPS = false;        // Block private IP ranges (stricter)
const WHITELIST_MODE = true;            // Protect all endpoints by default
const LOG_ENABLE = true;                // Enable logging for security monitoring
```

## Best Practices

### 1. API Key Management
- Generate strong random API keys (minimum 32 characters)
- Use a secure random generator: https://roste.org/rand/#rsgen
- Store keys in `keys/` directory (excluded from git)
- Rotate keys periodically

### 2. File Permissions
```bash
# Recommended permissions
chmod 640 keys/*.php
chmod 640 settings/*.php
chmod 660 api.log
chmod 660 endpoints_lastcalled.json
```

### 3. Web Server Configuration

**Apache** (.htaccess):
```apache
# Protect sensitive files
<FilesMatch "^(\.git|composer\.(json|lock)|\.env)">
    Order allow,deny
    Deny from all
</FilesMatch>

# Protect directories with sensitive data
RedirectMatch 404 ^/(keys|settings|aliases|endpoints)/

# Disable directory listing
Options -Indexes
```

**Nginx**:
```nginx
# Protect sensitive files and directories
location ~ ^/(keys|settings|aliases|endpoints|\.git|composer\.(json|lock)|\.env) {
    deny all;
    return 404;
}
```

### 4. Rate Limiting
- Enable `COOLDOWN_TIME` to prevent abuse
- Set appropriate `SLEEP_TIME` values
- Use `noTimeOut` sparingly and only for trusted keys

### 5. Endpoint Security
- Use `WHITELIST_MODE = true` (protect by default)
- Only add endpoints to `OPEN_ENDPOINTS` if absolutely necessary
- Review endpoint access regularly

### 6. Logging and Monitoring
- Enable `LOG_ENABLE = true` in production
- Monitor logs for suspicious patterns:
  - Multiple failed authentication attempts
  - Unusual endpoint access patterns
  - Rate limiting violations
- Set appropriate `LOG_LEVEL` (INFO or WARNING in production)

### 7. HTTPS Only
Always deploy this API over HTTPS to prevent:
- Man-in-the-middle attacks
- API key interception
- Session hijacking

### 8. Regular Updates
- Keep PHP updated to the latest stable version
- Review security advisories regularly
- Update dependencies if any are added

## Security Checklist for Deployment

- [ ] Set `PRODUCTION_MODE = true`
- [ ] Configure `CORS_ALLOW_ORIGIN` to specific domain
- [ ] Review and secure file permissions
- [ ] Enable HTTPS only
- [ ] Generate strong, unique API keys
- [ ] Set appropriate `COOLDOWN_TIME` and `SLEEP_TIME`
- [ ] Configure `TRUST_PROXY` correctly based on infrastructure
- [ ] Enable logging and set up log rotation
- [ ] Review `OPEN_ENDPOINTS` - minimize open endpoints
- [ ] Set up web server security rules
- [ ] Test rate limiting functionality
- [ ] Verify error messages don't leak sensitive information

## Reporting Security Issues

If you discover a security vulnerability, please report it to the repository maintainer. Do not open a public issue for security vulnerabilities.

## Security Testing

### Testing for IP Spoofing
```bash
# Should not bypass rate limiting when TRUST_PROXY=false
curl -H "X-Forwarded-For: 1.2.3.4" http://yourapi/?endpoint=test
```

### Testing Rate Limiting
```bash
# Should get rate limited after first request
curl http://yourapi/?endpoint=test&apikey=YOUR_KEY
curl http://yourapi/?endpoint=test&apikey=YOUR_KEY  # Should fail
```

### Testing Input Validation
```bash
# Should reject invalid endpoint names
curl http://yourapi/?endpoint=../etc/passwd  # Should fail
curl http://yourapi/?endpoint=test%00        # Should fail
```

## Additional Resources

- [OWASP API Security Top 10](https://owasp.org/www-project-api-security/)
- [PHP Security Guide](https://www.php.net/manual/en/security.php)
- [CWE Top 25 Most Dangerous Software Weaknesses](https://cwe.mitre.org/top25/)
