# Swagger Documentation for Social Login APIs

This document provides information about the Swagger documentation that has been added for the Social Login APIs in the HealthDesk Plus application.

## Overview

The Social Login APIs have been fully documented with comprehensive Swagger annotations, making them available in the interactive API documentation alongside all other existing APIs.

## Swagger Documentation Location

- **API Documentation URL**: `http://localhost:8000/api/documentation`
- **Generated Documentation File**: `storage/api-docs/api-docs.json`

## Social Login API Endpoints in Swagger

The following social authentication endpoints are now documented in Swagger under the "Authentication - Social" tag:

### 1. Google OAuth Redirect
- **Endpoint**: `GET /api/auth/social/google/redirect`
- **Description**: Redirects user to Google OAuth consent screen
- **Response**: Returns redirect URL for Google OAuth flow

### 2. Google OAuth Callback
- **Endpoint**: `GET /api/auth/social/google/callback`
- **Description**: Handles Google OAuth callback and authenticates/registers user
- **Parameters**: 
  - `code` (query, required): Authorization code from Google
  - `state` (query, optional): State parameter for CSRF protection
- **Responses**: 
  - 200: Login successful
  - 201: Registration and login successful
  - 500: Google authentication failed

### 3. Facebook OAuth Redirect
- **Endpoint**: `GET /api/auth/social/facebook/redirect`
- **Description**: Redirects user to Facebook OAuth consent screen
- **Response**: Returns redirect URL for Facebook OAuth flow

### 4. Facebook OAuth Callback
- **Endpoint**: `GET /api/auth/social/facebook/callback`
- **Description**: Handles Facebook OAuth callback and authenticates/registers user
- **Parameters**: 
  - `code` (query, required): Authorization code from Facebook
  - `state` (query, optional): State parameter for CSRF protection
- **Responses**: 
  - 200: Login successful
  - 201: Registration and login successful
  - 500: Facebook authentication failed

### 5. Mobile App Social Login
- **Endpoint**: `POST /api/auth/social/login-with-token`
- **Description**: Authenticate using social provider access token (for mobile applications)
- **Request Body**:
  - `provider` (required): Social authentication provider ("google" or "facebook")
  - `access_token` (required): Access token from the social provider
- **Responses**: 
  - 200: Login successful
  - 201: Registration and login successful
  - 401: Invalid access token
  - 422: Validation error
  - 500: Authentication failed

## Swagger Documentation Features

### Comprehensive Response Examples
Each endpoint includes detailed response examples showing:
- Success responses with user data and authentication tokens
- Error responses with appropriate error messages
- Different response codes for various scenarios

### Request/Response Schemas
- Detailed request body schemas for POST endpoints
- Query parameter documentation for GET endpoints
- Response schemas with proper data types and examples

### Error Handling Documentation
- Validation errors (422)
- Authentication errors (401)
- Server errors (500)
- Provider-specific error messages

## How to Access Swagger Documentation

1. **Start the Laravel development server**:
   ```bash
   php artisan serve
   ```

2. **Open your browser and navigate to**:
   ```
   http://localhost:8000/api/documentation
   ```

3. **Navigate to the Social Authentication section**:
   - Look for the "Authentication - Social" tag in the Swagger UI
   - Expand the tag to see all social login endpoints

## Testing APIs through Swagger UI

### Web OAuth Flow Testing
1. Click on the Google/Facebook redirect endpoint
2. Click "Try it out" and then "Execute"
3. Copy the redirect URL from the response
4. Open the URL in a browser to test the OAuth flow

### Mobile Token Testing
1. Click on the "Social login with access token" endpoint
2. Click "Try it out"
3. Enter the request body with provider and access_token
4. Click "Execute" to test the authentication

## Swagger Annotations Used

The following OpenAPI annotations were used to document the endpoints:

- `@OA\Get` / `@OA\Post`: Define HTTP method and path
- `@OA\Parameter`: Document query parameters
- `@OA\RequestBody`: Document request body for POST endpoints
- `@OA\Response`: Document various response scenarios
- `@OA\JsonContent`: Define JSON response structure
- `@OA\Property`: Define individual properties in responses
- `@OA\Schema`: Define data types and validation rules

## Integration with Existing Documentation

The Social Login APIs are seamlessly integrated with the existing API documentation:
- Uses the same response format as other authentication endpoints
- Follows the same error handling patterns
- Maintains consistency with existing API documentation style
- Uses the same authentication token format (Bearer tokens)

## Benefits of Swagger Documentation

1. **Interactive Testing**: Developers can test endpoints directly from the documentation
2. **Clear Examples**: Comprehensive examples for all request/response scenarios
3. **Error Documentation**: Detailed error responses help with debugging
4. **Client SDK Generation**: Can be used to generate client SDKs
5. **API Validation**: Helps ensure API consistency and completeness

## Updating Documentation

To update the Swagger documentation after making changes:

1. **Regenerate the documentation**:
   ```bash
   php artisan l5-swagger:generate
   ```

2. **Clear any caches if needed**:
   ```bash
   php artisan config:clear
   php artisan route:clear
   ```

3. **Refresh the Swagger UI** in your browser

## Notes

- The Swagger documentation is automatically generated from the OpenAPI annotations in the controller
- All endpoints include proper error handling documentation
- The documentation follows OpenAPI 3.0 specification
- Response examples use realistic data for better understanding
- The documentation is accessible at `/api/documentation` route

## Related Files

- **Controller**: `app/Http/Controllers/Api/SocialAuthController.php`
- **Routes**: `routes/api.php` (social auth routes)
- **Configuration**: `config/l5-swagger.php`
- **Generated Docs**: `storage/api-docs/api-docs.json`
- **Setup Guide**: `SOCIAL_LOGIN_SETUP_GUIDE.md`
- **API Documentation**: `SOCIAL_LOGIN_API_DOCUMENTATION.md`
