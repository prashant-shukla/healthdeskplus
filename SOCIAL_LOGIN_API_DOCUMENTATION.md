# Social Login API Documentation

This document provides comprehensive information about the social login APIs for Google and Facebook authentication in the HealthDesk Plus application.

## Overview

The social login feature allows users to authenticate using their Google or Facebook accounts. The system supports both web-based OAuth flows and mobile app token-based authentication.

## Configuration

### Environment Variables

Add the following environment variables to your `.env` file:

```env
# Google OAuth Configuration
GOOGLE_CLIENT_ID=your_google_client_id
GOOGLE_CLIENT_SECRET=your_google_client_secret
GOOGLE_REDIRECT_URI=http://your-domain.com/api/auth/social/google/callback

# Facebook OAuth Configuration
FACEBOOK_CLIENT_ID=your_facebook_app_id
FACEBOOK_CLIENT_SECRET=your_facebook_app_secret
FACEBOOK_REDIRECT_URI=http://your-domain.com/api/auth/social/facebook/callback
```

### Setting up OAuth Applications

#### Google OAuth Setup
1. Go to [Google Cloud Console](https://console.cloud.google.com/)
2. Create a new project or select existing one
3. Enable Google+ API
4. Go to "Credentials" → "Create Credentials" → "OAuth 2.0 Client IDs"
5. Set authorized redirect URIs to include your callback URL
6. Copy the Client ID and Client Secret to your `.env` file

#### Facebook OAuth Setup
1. Go to [Facebook Developers](https://developers.facebook.com/)
2. Create a new app
3. Add Facebook Login product
4. Set valid OAuth redirect URIs in Facebook Login settings
5. Copy the App ID and App Secret to your `.env` file

## API Endpoints

### 1. Google OAuth Redirect

**Endpoint:** `GET /api/auth/social/google/redirect`

**Description:** Redirects user to Google OAuth consent screen.

**Response:**
```json
{
    "success": true,
    "redirect_url": "https://accounts.google.com/oauth/authorize?..."
}
```

### 2. Google OAuth Callback

**Endpoint:** `GET /api/auth/social/google/callback`

**Description:** Handles Google OAuth callback and authenticates/registers user.

**Response:**
```json
{
    "success": true,
    "message": "Login successful",
    "data": {
        "user": {
            "id": 1,
            "name": "John Doe",
            "email": "john@example.com",
            "user_type": "patient",
            "avatar": "https://lh3.googleusercontent.com/...",
            "provider": "google"
        },
        "token": "1|abc123...",
        "token_type": "Bearer"
    }
}
```

### 3. Facebook OAuth Redirect

**Endpoint:** `GET /api/auth/social/facebook/redirect`

**Description:** Redirects user to Facebook OAuth consent screen.

**Response:**
```json
{
    "success": true,
    "redirect_url": "https://www.facebook.com/v18.0/dialog/oauth?..."
}
```

### 4. Facebook OAuth Callback

**Endpoint:** `GET /api/auth/social/facebook/callback`

**Description:** Handles Facebook OAuth callback and authenticates/registers user.

**Response:**
```json
{
    "success": true,
    "message": "Login successful",
    "data": {
        "user": {
            "id": 1,
            "name": "John Doe",
            "email": "john@example.com",
            "user_type": "patient",
            "avatar": "https://platform-lookaside.fbsbx.com/...",
            "provider": "facebook"
        },
        "token": "1|abc123...",
        "token_type": "Bearer"
    }
}
```

### 5. Mobile App Social Login

**Endpoint:** `POST /api/auth/social/login-with-token`

**Description:** Authenticate using social provider access token (for mobile apps).

**Request Body:**
```json
{
    "provider": "google",
    "access_token": "ya29.a0AfH6SMC..."
}
```

**Response:**
```json
{
    "success": true,
    "message": "Login successful",
    "data": {
        "user": {
            "id": 1,
            "name": "John Doe",
            "email": "john@example.com",
            "user_type": "patient",
            "avatar": "https://lh3.googleusercontent.com/...",
            "provider": "google"
        },
        "token": "1|abc123...",
        "token_type": "Bearer"
    }
}
```

## User Flow

### Web Application Flow

1. User clicks "Login with Google/Facebook" button
2. Frontend calls the redirect endpoint (`/api/auth/social/google/redirect` or `/api/auth/social/facebook/redirect`)
3. User is redirected to the OAuth provider's consent screen
4. User grants permission and is redirected back to the callback URL
5. Backend processes the callback and returns user data with authentication token
6. Frontend stores the token and redirects user to dashboard

### Mobile Application Flow

1. User clicks "Login with Google/Facebook" button
2. Mobile app uses native SDK to authenticate with the provider
3. Mobile app receives access token from the provider
4. Mobile app sends the access token to `/api/auth/social/login-with-token`
5. Backend validates the token and returns user data with authentication token
6. Mobile app stores the token and proceeds to authenticated state

## Error Handling

All endpoints return consistent error responses:

```json
{
    "success": false,
    "message": "Error description",
    "error": "Detailed error message"
}
```

Common error scenarios:
- Invalid OAuth configuration
- Provider authentication failure
- Network connectivity issues
- Invalid access token (for mobile login)

## Security Considerations

1. **HTTPS Required:** All OAuth redirect URIs must use HTTPS in production
2. **State Parameter:** Consider implementing state parameter for CSRF protection
3. **Token Storage:** Store authentication tokens securely on client side
4. **Token Expiration:** Implement token refresh mechanism
5. **Scope Permissions:** Request minimal required permissions from OAuth providers

## Database Schema

The following fields have been added to the `users` table:

- `provider` (string, nullable): The OAuth provider name (google, facebook)
- `provider_id` (string, nullable): The user's ID from the OAuth provider
- `avatar` (string, nullable): User's profile picture URL

## Testing

### Manual Testing

1. **Google OAuth Test:**
   ```bash
   curl -X GET "http://localhost:8000/api/auth/social/google/redirect"
   ```

2. **Facebook OAuth Test:**
   ```bash
   curl -X GET "http://localhost:8000/api/auth/social/facebook/redirect"
   ```

3. **Mobile Login Test:**
   ```bash
   curl -X POST "http://localhost:8000/api/auth/social/login-with-token" \
   -H "Content-Type: application/json" \
   -d '{
       "provider": "google",
       "access_token": "your_access_token_here"
   }'
   ```

### Postman Collection

Import the following endpoints into Postman for testing:

1. `GET {{base_url}}/api/auth/social/google/redirect`
2. `GET {{base_url}}/api/auth/social/google/callback`
3. `GET {{base_url}}/api/auth/social/facebook/redirect`
4. `GET {{base_url}}/api/auth/social/facebook/callback`
5. `POST {{base_url}}/api/auth/social/login-with-token`

## Implementation Notes

1. **User Creation:** New users are created with `user_type` set to "patient" by default
2. **Email Verification:** Social login users are automatically marked as email verified
3. **Password:** Social login users get a random password (they won't use it)
4. **Avatar:** Profile pictures are automatically fetched from the OAuth provider
5. **Existing Users:** If a user with the same email exists, their account is linked to the OAuth provider

## Troubleshooting

### Common Issues

1. **"Invalid redirect URI"**
   - Check that your redirect URIs match exactly in OAuth provider settings
   - Ensure HTTPS is used in production

2. **"Client ID not found"**
   - Verify environment variables are set correctly
   - Restart the application after changing .env file

3. **"Access denied"**
   - Check OAuth provider app settings
   - Ensure required permissions are granted

4. **"Invalid access token"**
   - Verify token is not expired
   - Check token format and permissions

### Debug Mode

Enable debug logging by setting `LOG_LEVEL=debug` in your `.env` file to see detailed error messages.

## Support

For issues related to social login implementation, check:
1. Laravel Socialite documentation
2. Google OAuth 2.0 documentation
3. Facebook Login documentation
4. Application logs in `storage/logs/laravel.log`
