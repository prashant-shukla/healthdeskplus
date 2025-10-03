# Google OAuth Setup Guide

This guide will help you fix the "Missing required parameter: redirect_uri" error you're encountering with Google OAuth.

## The Problem

When you call `/api/auth/social/google/redirect`, you're getting a URL like:
```
https://accounts.google.com/o/oauth2/auth?scope=openid+profile+email&response_type=code
```

But when you visit this URL, Google shows:
> **Error 400: invalid_request**  
> **Missing required parameter: redirect_uri**

This happens because the Google OAuth configuration is incomplete.

## Solution: Complete Google OAuth Setup

### Step 1: Create Google OAuth Application

1. **Go to Google Cloud Console:**
   - Visit [Google Cloud Console](https://console.cloud.google.com/)

2. **Create or Select Project:**
   - Create a new project or select an existing one
   - Note down your project ID

3. **Enable Google+ API:**
   - Go to "APIs & Services" → "Library"
   - Search for "Google+ API" and enable it
   - Also enable "Google Identity" API if available

4. **Create OAuth 2.0 Credentials:**
   - Go to "APIs & Services" → "Credentials"
   - Click "Create Credentials" → "OAuth 2.0 Client IDs"
   - Choose "Web application"

5. **Configure OAuth Client:**
   - **Name:** HealthDesk Plus (or your app name)
   - **Authorized JavaScript origins:**
     ```
     http://localhost:8000
     https://yourdomain.com (for production)
     ```
   - **Authorized redirect URIs:**
     ```
     http://localhost:8000/api/auth/social/google/callback
     https://yourdomain.com/api/auth/social/google/callback (for production)
     ```

6. **Copy Credentials:**
   - Copy the **Client ID** and **Client Secret**
   - These will be used in your `.env` file

### Step 2: Configure Environment Variables

Add these variables to your `.env` file:

```env
# Google OAuth Configuration
GOOGLE_CLIENT_ID=your_google_client_id_here
GOOGLE_CLIENT_SECRET=your_google_client_secret_here
GOOGLE_REDIRECT_URI=http://localhost:8000/api/auth/social/google/callback
```

**Important Notes:**
- Replace `your_google_client_id_here` with your actual Google Client ID
- Replace `your_google_client_secret_here` with your actual Google Client Secret
- The redirect URI must match exactly what you configured in Google Cloud Console

### Step 3: Clear Configuration Cache

After updating the `.env` file:

```bash
php artisan config:clear
php artisan cache:clear
```

### Step 4: Test the Configuration

Test the Google OAuth redirect:

```bash
curl -X GET http://localhost:8000/api/auth/social/google/redirect
```

**Expected Success Response:**
```json
{
    "success": true,
    "redirect_url": "https://accounts.google.com/o/oauth2/auth?client_id=YOUR_CLIENT_ID&redirect_uri=http://localhost:8000/api/auth/social/google/callback&scope=openid+profile+email&response_type=code&state=..."
}
```

**If Still Getting Error:**
```json
{
    "success": false,
    "message": "Google OAuth is not properly configured. Please check your environment variables.",
    "error": "Missing Google OAuth configuration. Required: GOOGLE_CLIENT_ID, GOOGLE_CLIENT_SECRET, GOOGLE_REDIRECT_URI"
}
```

This means the environment variables are still not loaded. Try:
1. Restart your Laravel server: `php artisan serve`
2. Check if `.env` file is in the project root
3. Verify there are no spaces around the `=` sign in `.env`

## Production Setup

For production deployment:

1. **Update Redirect URIs in Google Console:**
   ```
   https://yourdomain.com/api/auth/social/google/callback
   ```

2. **Update Environment Variables:**
   ```env
   GOOGLE_REDIRECT_URI=https://yourdomain.com/api/auth/social/google/callback
   ```

3. **Use HTTPS:**
   - Google OAuth requires HTTPS in production
   - Ensure your domain has a valid SSL certificate

## Testing the Complete Flow

Once configured properly:

1. **Get Redirect URL:**
   ```bash
   curl -X GET http://localhost:8000/api/auth/social/google/redirect
   ```

2. **Visit the Redirect URL:**
   - Open the `redirect_url` from the response in your browser
   - You should see Google's consent screen (not the error)

3. **Complete OAuth Flow:**
   - Sign in with Google
   - Grant permissions
   - You'll be redirected to: `http://localhost:8000/api/auth/social/google/callback?code=...`

4. **Test Mobile Login:**
   ```bash
   curl -X POST http://localhost:8000/api/auth/social/login-with-token \
     -H "Content-Type: application/json" \
     -d '{
       "provider": "google",
       "access_token": "your_google_access_token"
     }'
   ```

## Common Issues & Solutions

### Issue 1: "redirect_uri_mismatch"
**Solution:** Ensure the redirect URI in Google Console exactly matches your `.env` configuration.

### Issue 2: "invalid_client"
**Solution:** Check that your Client ID and Client Secret are correct.

### Issue 3: "access_denied"
**Solution:** User denied permission or OAuth consent screen not configured.

### Issue 4: Environment Variables Not Loading
**Solutions:**
- Restart Laravel server
- Check `.env` file syntax
- Ensure no extra spaces in `.env`
- Run `php artisan config:clear`

### Issue 5: HTTPS Required in Production
**Solution:** Google OAuth requires HTTPS for production domains.

## Security Best Practices

1. **Keep Credentials Secure:**
   - Never commit `.env` file to version control
   - Use different credentials for development and production

2. **Validate Redirect URIs:**
   - Only add necessary redirect URIs
   - Remove unused redirect URIs

3. **Monitor Usage:**
   - Check Google Cloud Console for unusual activity
   - Set up quota limits if needed

## Facebook OAuth Setup

If you also want to set up Facebook OAuth:

1. **Create Facebook App:**
   - Go to [Facebook Developers](https://developers.facebook.com/)
   - Create a new app
   - Add Facebook Login product

2. **Configure OAuth Settings:**
   - Valid OAuth Redirect URIs:
     ```
     http://localhost:8000/api/auth/social/facebook/callback
     ```

3. **Add to .env:**
   ```env
   FACEBOOK_CLIENT_ID=your_facebook_app_id
   FACEBOOK_CLIENT_SECRET=your_facebook_app_secret
   FACEBOOK_REDIRECT_URI=http://localhost:8000/api/auth/social/facebook/callback
   ```

## Support

If you're still experiencing issues:

1. **Check Laravel Logs:**
   ```bash
   tail -f storage/logs/laravel.log
   ```

2. **Verify Configuration:**
   ```bash
   php artisan config:show services.google
   ```

3. **Test Environment Variables:**
   ```bash
   php artisan tinker
   >>> config('services.google.client_id')
   >>> config('services.google.redirect')
   ```

The key to fixing the "Missing required parameter: redirect_uri" error is ensuring that:
1. Google OAuth application is properly configured with correct redirect URIs
2. Environment variables are set correctly in `.env`
3. Laravel configuration cache is cleared after changes
