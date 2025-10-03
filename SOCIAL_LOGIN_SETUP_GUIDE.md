# Social Login Setup Guide

This guide will help you set up Google and Facebook social login for your HealthDesk Plus application.

## Quick Setup Steps

### 1. Environment Configuration

Add the following variables to your `.env` file:

```env
# Google OAuth Configuration
GOOGLE_CLIENT_ID=your_google_client_id_here
GOOGLE_CLIENT_SECRET=your_google_client_secret_here
GOOGLE_REDIRECT_URI=http://localhost:8000/api/auth/social/google/callback

# Facebook OAuth Configuration
FACEBOOK_CLIENT_ID=your_facebook_app_id_here
FACEBOOK_CLIENT_SECRET=your_facebook_app_secret_here
FACEBOOK_REDIRECT_URI=http://localhost:8000/api/auth/social/facebook/callback
```

### 2. Google OAuth Setup

1. Go to [Google Cloud Console](https://console.cloud.google.com/)
2. Create a new project or select an existing one
3. Enable the Google+ API (or Google Identity API)
4. Go to "Credentials" → "Create Credentials" → "OAuth 2.0 Client IDs"
5. Choose "Web application"
6. Add authorized redirect URIs:
   - `http://localhost:8000/api/auth/social/google/callback` (for development)
   - `https://yourdomain.com/api/auth/social/google/callback` (for production)
7. Copy the Client ID and Client Secret to your `.env` file

### 3. Facebook OAuth Setup

1. Go to [Facebook Developers](https://developers.facebook.com/)
2. Create a new app and choose "Consumer" type
3. Add the "Facebook Login" product
4. In Facebook Login settings, add valid OAuth redirect URIs:
   - `http://localhost:8000/api/auth/social/facebook/callback` (for development)
   - `https://yourdomain.com/api/auth/social/facebook/callback` (for production)
5. Copy the App ID and App Secret to your `.env` file

### 4. Test the Implementation

1. Clear configuration cache:
   ```bash
   php artisan config:clear
   php artisan route:clear
   ```

2. Start your development server:
   ```bash
   php artisan serve
   ```

3. Test the endpoints:
   - **Google redirect:** `GET http://localhost:8000/api/auth/social/google/redirect`
   - **Facebook redirect:** `GET http://localhost:8000/api/auth/social/facebook/redirect`
   - **Mobile login:** `POST http://localhost:8000/api/auth/social/login-with-token`

### 5. Frontend Integration Examples

#### Web Application (JavaScript)

```javascript
// Google Login
async function loginWithGoogle() {
    try {
        const response = await fetch('/api/auth/social/google/redirect');
        const data = await response.json();
        
        if (data.success) {
            window.location.href = data.redirect_url;
        }
    } catch (error) {
        console.error('Google login failed:', error);
    }
}

// Facebook Login
async function loginWithFacebook() {
    try {
        const response = await fetch('/api/auth/social/facebook/redirect');
        const data = await response.json();
        
        if (data.success) {
            window.location.href = data.redirect_url;
        }
    } catch (error) {
        console.error('Facebook login failed:', error);
    }
}
```

#### Mobile Application (React Native)

```javascript
import { GoogleSignin } from '@react-native-google-signin/google-signin';
import { LoginManager, AccessToken } from 'react-native-fbsdk-next';

// Google Login
const loginWithGoogle = async () => {
    try {
        await GoogleSignin.hasPlayServices();
        const userInfo = await GoogleSignin.signIn();
        const accessToken = userInfo.accessToken;
        
        const response = await fetch('http://your-api-url/api/auth/social/login-with-token', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({
                provider: 'google',
                access_token: accessToken
            })
        });
        
        const data = await response.json();
        if (data.success) {
            // Store token and redirect to authenticated state
            await AsyncStorage.setItem('auth_token', data.data.token);
        }
    } catch (error) {
        console.error('Google login failed:', error);
    }
};

// Facebook Login
const loginWithFacebook = async () => {
    try {
        const result = await LoginManager.logInWithPermissions(['public_profile', 'email']);
        
        if (result.isCancelled) {
            return;
        }
        
        const data = await AccessToken.getCurrentAccessToken();
        const accessToken = data.accessToken;
        
        const response = await fetch('http://your-api-url/api/auth/social/login-with-token', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({
                provider: 'facebook',
                access_token: accessToken
            })
        });
        
        const responseData = await response.json();
        if (responseData.success) {
            // Store token and redirect to authenticated state
            await AsyncStorage.setItem('auth_token', responseData.data.token);
        }
    } catch (error) {
        console.error('Facebook login failed:', error);
    }
};
```

## Troubleshooting

### Common Issues

1. **"Invalid redirect URI"**
   - Ensure redirect URIs in OAuth provider settings match exactly
   - Use HTTPS in production
   - Check for trailing slashes

2. **"Client ID not found"**
   - Verify `.env` file has correct values
   - Restart server after changing `.env`
   - Check for typos in environment variable names

3. **"Access denied"**
   - Check OAuth provider app status
   - Verify required permissions are granted
   - Ensure app is not in development mode restrictions

### Debug Mode

Enable detailed logging by setting in `.env`:
```env
LOG_LEVEL=debug
```

Check logs at `storage/logs/laravel.log` for detailed error messages.

## Security Notes

1. **Production Setup:**
   - Always use HTTPS for redirect URIs in production
   - Keep client secrets secure and never expose them in frontend code
   - Implement proper CORS settings

2. **Token Management:**
   - Store authentication tokens securely
   - Implement token refresh mechanism
   - Set appropriate token expiration times

3. **User Data:**
   - Request only necessary permissions from OAuth providers
   - Validate and sanitize user data from social providers
   - Implement proper user profile update flows

## API Endpoints Summary

| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | `/api/auth/social/google/redirect` | Redirect to Google OAuth |
| GET | `/api/auth/social/google/callback` | Handle Google callback |
| GET | `/api/auth/social/facebook/redirect` | Redirect to Facebook OAuth |
| GET | `/api/auth/social/facebook/callback` | Handle Facebook callback |
| POST | `/api/auth/social/login-with-token` | Mobile app login with token |

For detailed API documentation, see `SOCIAL_LOGIN_API_DOCUMENTATION.md`.
