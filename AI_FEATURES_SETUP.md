# AI Features Setup Guide

## Required Environment Variables

To use the AI features, you need to set up the following environment variables in your `.env` file:

### 1. Google Services

```env
# Google Places API (for Smart Autocomplete)
GOOGLE_PLACES_API_KEY=your_google_places_api_key

# Google Cloud Vision (for Document OCR)
GOOGLE_VISION_CREDENTIALS_PATH=/path/to/your/credentials.json

# Google Translate API (for Language Support)
GOOGLE_TRANSLATE_API_KEY=your_google_translate_api_key
```

### 2. OpenAI

```env
# OpenAI API (for AI features)
OPENAI_API_KEY=your_openai_api_key
```

## Setup Instructions

### 1. Create .env file

Create a `.env` file in your project root with the following content:

```env
APP_NAME=HealthDeskPlus
APP_ENV=local
APP_KEY=base64:your_app_key_here
APP_DEBUG=true
APP_TIMEZONE=UTC
APP_URL=http://localhost:8000

APP_LOCALE=en
APP_FALLBACK_LOCALE=en
APP_FAKER_LOCALE=en_US

APP_MAINTENANCE_DRIVER=file
APP_MAINTENANCE_STORE=database

BCRYPT_ROUNDS=12

LOG_CHANNEL=stack
LOG_STACK=single
LOG_DEPRECATIONS_CHANNEL=null
LOG_LEVEL=debug

DB_CONNECTION=sqlite
DB_DATABASE=/Users/prashantshukla/Sites/git/healthdeskplus/database/database.sqlite

SESSION_DRIVER=database
SESSION_LIFETIME=120
SESSION_ENCRYPT=false
SESSION_PATH=/
SESSION_DOMAIN=null

BROADCAST_CONNECTION=log
FILESYSTEM_DISK=local
QUEUE_CONNECTION=database

CACHE_STORE=database
CACHE_PREFIX=

MEMCACHED_HOST=127.0.0.1

REDIS_CLIENT=phpredis
REDIS_HOST=127.0.0.1
REDIS_PASSWORD=null
REDIS_PORT=6379

MAIL_MAILER=log
MAIL_HOST=127.0.0.1
MAIL_PORT=2525
MAIL_USERNAME=null
MAIL_PASSWORD=null
MAIL_ENCRYPTION=null
MAIL_FROM_ADDRESS="hello@example.com"
MAIL_FROM_NAME="${APP_NAME}"

AWS_ACCESS_KEY_ID=
AWS_SECRET_ACCESS_KEY=
AWS_DEFAULT_REGION=us-east-1
AWS_BUCKET=
AWS_USE_PATH_STYLE_ENDPOINT=false

VITE_APP_NAME="${APP_NAME}"

# Google Services
GOOGLE_CLIENT_ID=your_google_client_id
GOOGLE_CLIENT_SECRET=your_google_client_secret
GOOGLE_REDIRECT_URI=http://localhost:8000/auth/social/google/callback
GOOGLE_PLACES_API_KEY=your_google_places_api_key
GOOGLE_VISION_CREDENTIALS_PATH=/Users/prashantshukla/Sites/git/healthdeskplus/storage/app/healthdeskplus-e2de6e544502.json
GOOGLE_TRANSLATE_API_KEY=your_google_translate_api_key

# OpenAI
OPENAI_API_KEY=your_openai_api_key

# Facebook
FACEBOOK_CLIENT_ID=your_facebook_client_id
FACEBOOK_CLIENT_SECRET=your_facebook_client_secret
FACEBOOK_REDIRECT_URI=http://localhost:8000/auth/social/facebook/callback

# Postmark
POSTMARK_TOKEN=

# Resend
RESEND_KEY=

# Slack
SLACK_BOT_USER_OAUTH_TOKEN=
SLACK_BOT_USER_DEFAULT_CHANNEL=
```

### 2. Generate Application Key

Run the following command to generate your application key:

```bash
php artisan key:generate
```

### 3. Get API Keys

#### Google Places API
1. Go to [Google Cloud Console](https://console.cloud.google.com/)
2. Create a new project or select existing one
3. Enable the Places API
4. Create credentials (API Key)
5. Restrict the API key to Places API
6. Add the key to your `.env` file

#### Google Translate API
1. In the same Google Cloud Console project
2. Enable the Cloud Translation API
3. Create a new API key specifically for server-side use
4. **CRITICAL**: Don't restrict this API key by HTTP referrer - this will block server-side requests
5. **Recommended restrictions for server-side use**:
   - **IP addresses**: Add your server's IP address
   - **Or no restrictions** for development (less secure but easier to test)
6. **DO NOT use**: HTTP referrer restrictions for server-side API keys
7. Add the key to your `.env` file

#### Google Cloud Vision API
1. In the same Google Cloud Console project
2. Enable the Cloud Vision API
3. Create a service account
4. Download the JSON credentials file
5. Place it in `storage/app/` directory
6. Update the path in your `.env` file

#### OpenAI API
1. Go to [OpenAI Platform](https://platform.openai.com/)
2. Create an account or sign in
3. Go to API Keys section
4. Create a new API key
5. Add the key to your `.env` file

### 4. Test the Setup

#### Test Translation API
```bash
curl -X 'POST' \
  'http://localhost:8000/api/ai/translate' \
  -H 'accept: application/json' \
  -H 'Content-Type: application/json' \
  -d '{
  "text": "Hello, how are you?",
  "target_language": "hi",
  "source_language": "en"
}'
```

#### Test Language Detection
```bash
curl -X 'POST' \
  'http://localhost:8000/api/ai/detect-language' \
  -H 'accept: application/json' \
  -H 'Content-Type: application/json' \
  -d '{
  "text": "नमस्ते, आप कैसे हैं?"
}'
```

#### Test Supported Languages
```bash
curl -X 'GET' \
  'http://localhost:8000/api/ai/languages' \
  -H 'accept: application/json'
```

### 5. Run Migrations

Make sure your database is set up:

```bash
php artisan migrate
```

### 6. Start the Server

```bash
php artisan serve
```

## Troubleshooting

### Common Issues

#### 1. "Class not found" errors
- Make sure you've installed the required packages:
  ```bash
  composer require google/cloud-translate google/cloud-vision
  ```

#### 2. API Key errors
- Verify your API keys are correct in the `.env` file
- Check if the APIs are enabled in Google Cloud Console
- Ensure the API keys have the necessary permissions
- **For Google Translate API**: Make sure the API key is not restricted by HTTP referrer. Use IP address restrictions or no restrictions for development

#### 2.1. Google Translate API Key Restrictions (Common Issue)
**Error**: `"Requests from referer <empty> are blocked"` or `"Method doesn't allow unregistered callers"`

**Solution**:
1. Go to [Google Cloud Console](https://console.cloud.google.com/)
2. Navigate to "APIs & Services" > "Credentials"
3. Find your Google Translate API key
4. Click on the key to edit it
5. Under "Application restrictions":
   - **Remove HTTP referrer restrictions** (this blocks server-side requests)
   - **Use IP addresses** instead (add your server's IP)
   - **Or select "None"** for development (less secure but easier to test)
6. Save the changes
7. Wait 5-10 minutes for changes to propagate

#### 2.2. Production Environment Issues
**Error**: `"Translation service not available"` or `"Method doesn't allow unregistered callers"` in production but works locally

**Solution**:
1. **Clear configuration cache** in production:
   ```bash
   php artisan config:clear
   php artisan config:cache
   ```

2. **Verify environment variables** are set correctly in production:
   ```bash
   php artisan config:show services.google
   ```

3. **Check API key restrictions** - ensure the API key allows requests from your production server's IP address

4. **Production mode**: The service automatically uses direct API calls in production environment instead of the Google Translate client

5. **Fallback mechanism**: The service includes a fallback to direct API calls if the Google Translate client fails

#### 2.3. Google Translate Client Issues in Production
**Error**: `"Translation service not available"` - Google Translate client not available

**Solution**:
- The service now automatically detects production environment and uses direct API calls
- No need to install Google Translate client in production
- Direct API calls are more reliable in production environments
- Both translation and language detection work via direct API calls

#### 3. Credentials file errors
- Make sure the Google Cloud Vision credentials file exists
- Check the file path in your `.env` file
- Verify the service account has Vision API permissions

#### 4. Rate limit errors
- Check your API quotas in Google Cloud Console
- Implement caching to reduce API calls
- Consider upgrading your API plan if needed

### Testing Individual Features

#### Test Translation API (After fixing API key restrictions)
```bash
# Test with curl
curl -X 'POST' \
  'https://api.healthdeskplus.com/api/ai/translate' \
  -H 'accept: application/json' \
  -H 'Content-Type: application/json' \
  -d '{
  "text": "Hello, how are you?",
  "target_language": "hi",
  "source_language": "en"
}'

# Expected response:
{
  "success": true,
  "data": {
    "text": "नमस्ते, आप कैसे हैं?",
    "source_language": "en",
    "target_language": "hi",
    "translated": true,
    "confidence": 1.0
  }
}
```

#### Test Language Detection
```bash
curl -X 'POST' \
  'https://api.healthdeskplus.com/api/ai/detect-language' \
  -H 'accept: application/json' \
  -H 'Content-Type: application/json' \
  -d '{
  "text": "नमस्ते, आप कैसे हैं?"
}'

# Expected response:
{
  "success": true,
  "data": {
    "language": "hi",
    "confidence": 0.95,
    "language_name": "Hindi",
    "native_name": "हिन्दी"
  }
}
```

#### Smart Autocomplete
```bash
curl -X 'POST' \
  'http://localhost:8000/api/ai/autocomplete/clinic' \
  -H 'accept: application/json' \
  -H 'Content-Type: application/json' \
  -d '{
  "input": "City Medical",
  "location": "Mumbai, India"
}'
```

#### Specialization Detection
```bash
curl -X 'POST' \
  'http://localhost:8000/api/ai/detect-specialization' \
  -H 'accept: application/json' \
  -H 'Content-Type: application/json' \
  -d '{
  "qualification": "MBBS, MD in Cardiology from AIIMS Delhi"
}'
```

#### Document Processing
```bash
curl -X 'POST' \
  'http://localhost:8000/api/ai/process-document' \
  -H 'accept: application/json' \
  -F 'document=@/path/to/your/document.pdf' \
  -F 'document_type=certificate' \
  -F 'doctor_id=1'
```

## Rate Limits

| Service | Free Tier | Paid Tier |
|---------|-----------|-----------|
| Google Places API | 1,000 requests/day | $0.017 per request |
| Google Translate API | 1M characters/month | $20 per 1M characters |
| Google Cloud Vision | 1,800 requests/minute | $1.50 per 1,000 requests |
| OpenAI API | $5 credit | $0.002 per 1K tokens |

## Security Notes

1. **Never commit API keys** to version control
2. **Use environment variables** for all sensitive data
3. **Restrict API keys** to specific APIs and IP addresses
4. **Monitor usage** regularly to detect unusual activity
5. **Rotate keys** periodically for security

## Support

If you encounter any issues:

1. Check the Laravel logs: `storage/logs/laravel.log`
2. Verify your API keys and permissions
3. Test individual endpoints to isolate issues
4. Check the troubleshooting section above
5. Create an issue in the project repository

## Next Steps

Once you have the basic setup working:

1. Test all AI features individually
2. Integrate the features into your frontend
3. Set up monitoring and logging
4. Configure caching for better performance
5. Implement error handling and fallbacks
