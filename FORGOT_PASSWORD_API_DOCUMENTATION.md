# Forgot Password API Documentation

This document provides information about the Forgot Password and Reset Password APIs that have been added to the HealthDesk Plus application.

## Overview

The Forgot Password functionality allows users to reset their passwords through email verification. The system sends a password reset link to the user's email address, and they can use the token from the email to set a new password.

## API Endpoints

### 1. Forgot Password

**Endpoint:** `POST /api/auth/forgot-password`

**Description:** Sends a password reset link to the user's email address.

**Request Body:**
```json
{
    "email": "user@example.com"
}
```

**Success Response (200):**
```json
{
    "success": true,
    "message": "Password reset link sent to your email address"
}
```

**Error Responses:**
- **422 - Email not found:**
```json
{
    "success": false,
    "message": "We couldn't find a user with that email address"
}
```

- **422 - Validation error:**
```json
{
    "success": false,
    "message": "Validation errors",
    "errors": {
        "email": ["The email field is required."]
    }
}
```

- **429 - Too many requests:**
```json
{
    "success": false,
    "message": "Please wait before retrying"
}
```

### 2. Reset Password

**Endpoint:** `POST /api/auth/reset-password`

**Description:** Resets the user's password using the token from the email.

**Request Body:**
```json
{
    "email": "user@example.com",
    "password": "newpassword123",
    "password_confirmation": "newpassword123",
    "token": "reset_token_from_email"
}
```

**Success Response (200):**
```json
{
    "success": true,
    "message": "Password has been reset successfully"
}
```

**Error Responses:**
- **422 - Invalid token:**
```json
{
    "success": false,
    "message": "Invalid or expired reset token"
}
```

- **422 - Validation error:**
```json
{
    "success": false,
    "message": "Validation errors",
    "errors": {
        "password": ["The password field must be at least 8 characters."]
    }
}
```

- **500 - Server error:**
```json
{
    "success": false,
    "message": "Password reset failed",
    "error": "Error details"
}
```

## Implementation Details

### Files Modified/Created

1. **AuthController.php** - Added `forgotPassword()` and `resetPassword()` methods
2. **ResetPasswordNotification.php** - Custom notification for password reset emails
3. **User.php** - Added `sendPasswordResetNotification()` method
4. **web.php** - Added required password reset routes
5. **password_reset_tokens table** - Database table for storing reset tokens

### Configuration

The password reset functionality uses Laravel's built-in password reset system with the following configuration:

- **Token Expiration:** 60 minutes (configurable in `config/auth.php`)
- **Throttling:** 60 seconds between requests (configurable in `config/auth.php`)
- **Email Template:** Custom notification with HealthDesk Plus branding

### Security Features

1. **Token Expiration:** Reset tokens expire after 60 minutes
2. **Rate Limiting:** Users are throttled to prevent abuse
3. **Secure Tokens:** Laravel generates cryptographically secure tokens
4. **Email Verification:** Reset links are sent only to registered email addresses
5. **Password Validation:** Strong password requirements enforced

### Email Template

The password reset email includes:
- HealthDesk Plus branding
- Clear call-to-action button
- Expiration time information
- Security notice for non-requested resets

## Usage Examples

### Frontend Integration

```javascript
// Forgot Password
async function forgotPassword(email) {
    try {
        const response = await fetch('/api/auth/forgot-password', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({ email })
        });
        
        const data = await response.json();
        
        if (data.success) {
            alert('Password reset link sent to your email');
        } else {
            alert(data.message);
        }
    } catch (error) {
        console.error('Error:', error);
    }
}

// Reset Password
async function resetPassword(email, password, passwordConfirmation, token) {
    try {
        const response = await fetch('/api/auth/reset-password', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({
                email,
                password,
                password_confirmation: passwordConfirmation,
                token
            })
        });
        
        const data = await response.json();
        
        if (data.success) {
            alert('Password reset successfully');
            // Redirect to login page
        } else {
            alert(data.message);
        }
    } catch (error) {
        console.error('Error:', error);
    }
}
```

### Mobile App Integration

```javascript
// React Native example
import AsyncStorage from '@react-native-async-storage/async-storage';

const forgotPassword = async (email) => {
    try {
        const response = await fetch('https://your-api-domain.com/api/auth/forgot-password', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({ email })
        });
        
        const data = await response.json();
        return data;
    } catch (error) {
        console.error('Forgot password error:', error);
        throw error;
    }
};

const resetPassword = async (email, password, passwordConfirmation, token) => {
    try {
        const response = await fetch('https://your-api-domain.com/api/auth/reset-password', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({
                email,
                password,
                password_confirmation: passwordConfirmation,
                token
            })
        });
        
        const data = await response.json();
        return data;
    } catch (error) {
        console.error('Reset password error:', error);
        throw error;
    }
};
```

## Testing

### Manual Testing

1. **Test Forgot Password with valid email:**
```bash
curl -X POST http://localhost:8000/api/auth/forgot-password \
  -H "Content-Type: application/json" \
  -d '{"email": "existing@example.com"}'
```

2. **Test Forgot Password with invalid email:**
```bash
curl -X POST http://localhost:8000/api/auth/forgot-password \
  -H "Content-Type: application/json" \
  -d '{"email": "nonexistent@example.com"}'
```

3. **Test Reset Password with valid token:**
```bash
curl -X POST http://localhost:8000/api/auth/reset-password \
  -H "Content-Type: application/json" \
  -d '{
    "email": "user@example.com",
    "password": "newpassword123",
    "password_confirmation": "newpassword123",
    "token": "valid_token_from_email"
  }'
```

### Swagger Documentation

The Forgot Password APIs are documented in Swagger UI under the "Authentication" tag:
- Visit `http://localhost:8000/api/documentation`
- Navigate to the "Authentication" section
- Find the "Send password reset link" and "Reset password" endpoints

## Troubleshooting

### Common Issues

1. **"Route [password.reset] not defined"**
   - Solution: Ensure the password reset routes are defined in `routes/web.php`

2. **Email not sending**
   - Check mail configuration in `.env`
   - Verify SMTP settings
   - Check Laravel logs for mail errors

3. **Token expired**
   - Tokens expire after 60 minutes
   - User needs to request a new reset link

4. **Rate limiting**
   - Users are limited to one request per minute
   - Wait before making another request

### Debug Mode

Enable debug logging by setting in `.env`:
```env
LOG_LEVEL=debug
MAIL_LOG_DRIVER=log
```

Check logs at `storage/logs/laravel.log` for detailed error messages.

## Security Considerations

1. **Email Security:** Never log or expose reset tokens
2. **Rate Limiting:** Implement additional rate limiting if needed
3. **Token Validation:** Always validate tokens server-side
4. **HTTPS:** Use HTTPS in production for secure token transmission
5. **Email Verification:** Consider adding email verification for new accounts

## Related APIs

- **Login:** `POST /api/auth/login`
- **Register:** `POST /api/auth/register` (for testing purposes only)
- **Social Login:** `POST /api/auth/social/login-with-token`

## Support

For issues related to password reset functionality:
1. Check Laravel logs for detailed error messages
2. Verify email configuration
3. Ensure database tables exist
4. Check token expiration settings
