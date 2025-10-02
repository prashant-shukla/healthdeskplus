# HealthDesk Plus API Documentation

## Base URL
```
http://localhost:8000/api
```

## Authentication
All protected endpoints require a Bearer token in the Authorization header:
```
Authorization: Bearer YOUR_TOKEN_HERE
```

## API Endpoints

### Authentication

#### 1. Register Doctor
**POST** `/api/auth/register`

**Request Body:**
```json
{
    "name": "Dr. John Doe",
    "email": "john@example.com",
    "password": "password123",
    "password_confirmation": "password123",
    "practice_name": "City Medical Center",
    "practice_type": "allopathy",
    "first_name": "John",
    "last_name": "Doe",
    "qualification": "MBBS, MD",
    "specialization": "General Medicine",
    "phone": "+91-9876543210"
}
```

**Response:**
```json
{
    "success": true,
    "message": "Registration successful",
    "data": {
        "user": {
            "id": 1,
            "name": "Dr. John Doe",
            "email": "john@example.com",
            "user_type": "doctor",
            "doctor": {
                "id": 1,
                "first_name": "John",
                "last_name": "Doe",
                "practice": {
                    "id": 1,
                    "name": "City Medical Center",
                    "type": "allopathy"
                }
            }
        },
        "token": "1|abc123...",
        "token_type": "Bearer"
    }
}
```

#### 2. Login
**POST** `/api/auth/login`

**Request Body:**
```json
{
    "email": "john@example.com",
    "password": "password123"
}
```

#### 3. Get Profile
**GET** `/api/auth/profile`

**Headers:**
```
Authorization: Bearer YOUR_TOKEN
```

#### 4. Update Profile
**PUT** `/api/auth/profile`

**Headers:**
```
Authorization: Bearer YOUR_TOKEN
```

**Request Body:**
```json
{
    "name": "Dr. John Smith",
    "bio": "Experienced physician with 10 years of practice",
    "specialization": "Cardiology",
    "experience_years": 10
}
```

#### 5. Logout
**POST** `/api/auth/logout`

**Headers:**
```
Authorization: Bearer YOUR_TOKEN
```

### Dashboard

#### Get Dashboard Stats
**GET** `/api/dashboard/stats`

**Headers:**
```
Authorization: Bearer YOUR_TOKEN
```

**Response:**
```json
{
    "success": true,
    "data": {
        "total_patients": 150,
        "total_appointments": 1200,
        "today_appointments": 8,
        "upcoming_appointments": 25,
        "completed_appointments": 1100,
        "total_prescriptions": 800,
        "total_medical_records": 950
    }
}
```

## Testing the API

### Using cURL

#### 1. Register a new doctor:
```bash
curl -X POST http://localhost:8000/api/auth/register \
  -H "Content-Type: application/json" \
  -d '{
    "name": "Dr. Jane Smith",
    "email": "jane@example.com",
    "password": "password123",
    "password_confirmation": "password123",
    "practice_name": "Wellness Clinic",
    "practice_type": "homeopathy",
    "first_name": "Jane",
    "last_name": "Smith",
    "qualification": "BHMS",
    "specialization": "Homeopathy",
    "phone": "+91-9876543211"
  }'
```

#### 2. Login:
```bash
curl -X POST http://localhost:8000/api/auth/login \
  -H "Content-Type: application/json" \
  -d '{
    "email": "jane@example.com",
    "password": "password123"
  }'
```

#### 3. Get profile (replace TOKEN with actual token):
```bash
curl -X GET http://localhost:8000/api/auth/profile \
  -H "Authorization: Bearer TOKEN"
```

#### 4. Get dashboard stats:
```bash
curl -X GET http://localhost:8000/api/dashboard/stats \
  -H "Authorization: Bearer TOKEN"
```

### Using Postman

1. Create a new collection called "HealthDesk Plus API"
2. Set the base URL to `http://localhost:8000/api`
3. Add the registration and login requests
4. For protected endpoints, add the Authorization header with Bearer token

## Next Steps

The following API endpoints are ready to be implemented:

1. **Patient Management** - CRUD operations for patients
2. **Appointment Management** - Schedule, confirm, cancel appointments
3. **Prescription Management** - Create and manage prescriptions
4. **Medical Records** - Store and retrieve medical records
5. **Practice Management** - Update practice settings

## Error Handling

All API responses follow this format:

**Success Response:**
```json
{
    "success": true,
    "message": "Operation successful",
    "data": { ... }
}
```

**Error Response:**
```json
{
    "success": false,
    "message": "Error description",
    "errors": { ... }
}
```

## Status Codes

- `200` - Success
- `201` - Created
- `401` - Unauthorized
- `403` - Forbidden
- `404` - Not Found
- `422` - Validation Error
- `500` - Server Error
