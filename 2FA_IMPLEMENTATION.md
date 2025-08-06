# Two-Factor Authentication (2FA) Implementation

## Overview

This implementation enforces 2FA verification for users who have enabled 2FA. Users with 2FA enabled must verify their 2FA code before accessing protected resources.

## How it Works

### 1. Login Flow
1. User logs in with email/password
2. If 2FA is enabled, the response includes `requires_2fa_verification: true`
3. User must verify their 2FA code before accessing protected resources

### 2. 2FA Verification
1. User calls `/api/auth/2fa/verify` with their 2FA code
2. If valid, the response includes instructions for subsequent requests
3. User must include `X-2FA-Verified: true` header in all protected resource requests

### 3. Protected Resources
- All restaurant, menu, and review endpoints require 2FA verification if enabled
- Auth management endpoints (enable/disable 2FA) don't require 2FA verification

## API Usage

### Login (2FA Enabled User)
```bash
POST /api/auth/login
{
    "email": "user@example.com",
    "password": "password"
}
```

Response:
```json
{
    "success": true,
    "message": "Login successful. 2FA verification required for protected resources.",
    "data": {
        "user": {...},
        "token": "jwt_token_here",
        "token_type": "bearer",
        "expires_in": 3600,
        "two_factor_enabled": true,
        "requires_2fa_verification": true
    }
}
```

### Verify 2FA Code
```bash
POST /api/auth/2fa/verify
Authorization: Bearer jwt_token_here
{
    "code": "123456"
}
```

Response:
```json
{
    "success": true,
    "message": "2FA code verified successfully",
    "data": {
        "two_factor_verified": true,
        "note": "Include X-2FA-Verified: true header in subsequent requests to protected resources"
    }
}
```

### Access Protected Resources
```bash
GET /api/restaurants
Authorization: Bearer jwt_token_here
X-2FA-Verified: true
```

## Error Responses

### 2FA Required (403)
```json
{
    "success": false,
    "message": "Two-factor authentication required",
    "requires_2fa": true
}
```

### Invalid 2FA Code (401)
```json
{
    "success": false,
    "message": "Invalid 2FA code"
}
```

## Middleware

- `auth.api:api` - Standard JWT authentication
- `require.2fa` - Enforces 2FA verification for users with 2FA enabled

## Routes

### No 2FA Required
- `POST /api/auth/2fa/enable` - Enable 2FA
- `POST /api/auth/2fa/disable` - Disable 2FA
- `POST /api/auth/2fa/verify` - Verify 2FA code
- `POST /api/auth/logout` - Logout
- `POST /api/auth/refresh` - Refresh token
- `GET /api/auth/me` - Get user info

### 2FA Required (if enabled)
- All restaurant endpoints (`/api/restaurants/*`)
- All menu endpoints (`/api/restaurants/{id}/menus/*`)
- All review endpoints (`/api/restaurants/{id}/reviews/*`)

## Security Notes

1. The `X-2FA-Verified: true` header must be included in all requests to protected resources
2. This header is checked by the `RequireTwoFactor` middleware
3. Users without 2FA enabled can access protected resources normally
4. The verification is stateless and relies on the client including the header 