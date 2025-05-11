```markdown
# API Documentation

This document provides detailed information about the available endpoints of the API. The API is divided into three main sections:

1. **User API** – Endpoints for user management, including registration, login, profile retrieval, and profile updates.
2. **Public API** – Public endpoints that do not require authentication, providing access to non-sensitive data.
3. **System Operations API** – Endpoints intended for system monitoring and administrative operations.

---

## Table of Contents

- [User API](#user-api)
  - [User Registration](#user-registration)
  - [User Login](#user-login)
  - [Get User Profile](#get-user-profile)
  - [Update User Profile](#update-user-profile)
- [Public API](#public-api)
  - [Get All Users](#get-all-users)
  - [Get Public User Profile](#get-public-user-profile)
- [System Operations API](#system-operations-api)
  - [System Status](#system-status)
  - [System Configuration Update](#system-configuration-update)
- [Error Handling](#error-handling)
- [Authentication](#authentication)
- [Additional Notes](#additional-notes)

---

## User API

The **User API** provides endpoints for managing user accounts. This section details functions for user registration, authentication, profile retrieval, and profile updates.

### User Registration

- **URL:** `/api/user/register`
- **Method:** `POST`
- **Description:**  
  Registers a new user in the system. The endpoint accepts user details and creates a new account. Upon success, a confirmation message is returned.

- **Request Body Parameters:**

  | Parameter | Type   | Required | Description                                 |
  |-----------|--------|----------|---------------------------------------------|
  | username  | string | Yes      | Desired username for the new account.       |
  | email     | string | Yes      | User's email address for registration.      |
  | password  | string | Yes      | Password to secure the account.             |

- **Example Request:**
  ```json
  {
    "username": "janek",
    "email": "janek@example.com",
    "password": "secret123"
  }
  ```

- **Example Response:**
  ```json
  {
    "status": "success",
    "message": "User registered successfully."
  }
  ```

- **Additional Details:**
  - Passwords are expected to be hashed on the server side.
  - A confirmation email may be sent to verify the provided email address.
  - In case of missing parameters or invalid data, the response will include an error message explaining the issue.

---

### User Login

- **URL:** `/api/user/login`
- **Method:** `POST`
- **Description:**  
  Authenticates an existing user using email and password credentials. On successful authentication, a JSON Web Token (JWT) is returned for session management.

- **Request Body Parameters:**

  | Parameter | Type   | Required | Description                           |
  |-----------|--------|----------|---------------------------------------|
  | email     | string | Yes      | User's registered email address.      |
  | password  | string | Yes      | Corresponding password for the account.|

- **Example Request:**
  ```json
  {
    "email": "janek@example.com",
    "password": "secret123"
  }
  ```

- **Example Response:**
  ```json
  {
    "status": "success",
    "token": "eyJhbGciOiJIUzI1NiIsInR..."
  }
  ```

- **Additional Details:**
  - The JWT token must be used in the `Authorization` header for all subsequent protected endpoints.
  - Tokens typically have an expiration time; refresh mechanisms should be implemented as needed.
  - If the credentials are invalid, an appropriate error message will be returned.

---

### Get User Profile

- **URL:** `/api/user/profile`
- **Method:** `GET`
- **Description:**  
  Retrieves the detailed profile information of the authenticated user. This endpoint requires a valid JWT token in the request header.

- **Headers:**
  - `Authorization: Bearer <token>`

- **Example Response:**
  ```json
  {
    "id": 1,
    "username": "janek",
    "email": "janek@example.com",
    "created_at": "2025-02-22T10:00:00Z"
  }
  ```

- **Additional Details:**
  - This endpoint returns information such as the user’s unique ID, username, email, and account creation date.
  - In the event of an invalid or expired token, an error response will be provided.

---

### Update User Profile

- **URL:** `/api/user/update`
- **Method:** `PUT`
- **Description:**  
  Allows an authenticated user to update their profile information. Partial updates are supported, meaning only the fields included in the request body will be modified.

- **Headers:**
  - `Authorization: Bearer <token>`

- **Request Body Parameters:**

  | Parameter | Type   | Required | Description                                           |
  |-----------|--------|----------|-------------------------------------------------------|
  | username  | string | No       | New username, if the user wishes to change it.        |
  | email     | string | No       | New email address, if updating is desired.          |
  | password  | string | No       | New password, if the user chooses to update their password.|

- **Example Request:**
  ```json
  {
    "username": "nowy_janek"
  }
  ```

- **Example Response:**
  ```json
  {
    "status": "success",
    "message": "User profile updated successfully."
  }
  ```

- **Additional Details:**
  - The system validates the provided data and updates only those fields present in the request.
  - For password changes, ensure that secure password handling practices are followed.
  - If any update fails (e.g., due to invalid data), an error response will specify the problem.

---

## Public API

The **Public API** provides endpoints that are accessible without authentication. These endpoints are designed for retrieving public, non-sensitive data.

### Get All Users

- **URL:** `/api/public/users`
- **Method:** `GET`
- **Description:**  
  Returns a list of all registered users with minimal public information. This endpoint is useful for public directories or listings.

- **Example Response:**
  ```json
  [
    {
      "id": 1,
      "username": "janek"
    },
    {
      "id": 2,
      "username": "ania"
    }
  ]
  ```

- **Additional Details:**
  - Only non-sensitive information is returned (e.g., user IDs and usernames).
  - No authentication is required to access this endpoint.

---

### Get Public User Profile

- **URL:** `/api/public/user/{id}`
- **Method:** `GET`
- **Description:**  
  Retrieves public profile information for a specific user based on their unique identifier.

- **Path Parameter:**

  | Parameter | Type | Required | Description                     |
  |-----------|------|----------|---------------------------------|
  | id        | int  | Yes      | Unique identifier of the user.  |

- **Example Response:**
  ```json
  {
    "id": 1,
    "username": "janek",
    "bio": "Programmer and coding enthusiast."
  }
  ```

- **Additional Details:**
  - This endpoint is designed to provide public information only.
  - Sensitive data such as email addresses and internal IDs are not exposed.
  - The profile may include additional public fields such as a user biography or social media links.

---

## System Operations API

The **System Operations API** consists of endpoints used for monitoring system status and performing administrative tasks. These endpoints should be secured and accessible only to authorized personnel.

### System Status

- **URL:** `/api/system/status`
- **Method:** `GET`
- **Description:**  
  Provides real-time information about the system’s health. This includes system uptime, current status, and version information.

- **Example Response:**
  ```json
  {
    "status": "ok",
    "uptime": "72 hours",
    "version": "1.0.0"
  }
  ```

- **Additional Details:**
  - This endpoint is intended for system monitoring and diagnostics.
  - It should be secured to prevent unauthorized access.
  - The uptime is typically returned in a human-readable format and can be useful for maintenance monitoring.

---

### System Configuration Update

- **URL:** `/api/system/config`
- **Method:** `POST`
- **Description:**  
  Allows authorized administrators to update system configuration settings dynamically.

- **Headers:**
  - `Authorization: Bearer <admin-token>`

- **Request Body Parameters:**

  | Parameter   | Type   | Required | Description                                      |
  |-------------|--------|----------|--------------------------------------------------|
  | configKey   | string | Yes      | The key or name of the configuration setting.    |
  | configValue | string | Yes      | The new value to be applied for the configuration.|

- **Example Request:**
  ```json
  {
    "configKey": "max_users",
    "configValue": "1000"
  }
  ```

- **Example Response:**
  ```json
  {
    "status": "success",
    "message": "System configuration updated successfully."
  }
  ```

- **Additional Details:**
  - Only users with administrative privileges should have access to this endpoint.
  - Changes may require additional processing such as system reloading or might be applied dynamically.
  - Validation is performed to ensure that only allowed configuration keys are updated.

---

## Error Handling

- **Error Response Format:**
  ```json
  {
    "status": "error",
    "message": "Detailed error description."
  }
  ```

- **Common Error Scenarios:**
  - **Missing or Invalid Parameters:** If required parameters are missing or invalid, an error message specifying the issue is returned.
  - **Authentication Failures:** When the JWT token is missing, invalid, or expired, an error response will indicate the authentication failure.
  - **Unauthorized Access:** Attempts to access protected endpoints without proper credentials will result in an error.
  - **Internal Server Errors:** In cases of server-side failures, a generic error message is provided.

---

## Authentication

- **JWT Token Requirement:**  
  Most protected endpoints require a valid JWT token. Include the token in the request header as follows:
  ```
  Authorization: Bearer <token>
  ```
- **Token Expiration:**  
  Tokens have a limited lifespan. Ensure that your application handles token expiration and renewal appropriately.
- **Secure Transmission:**  
  Always use HTTPS to ensure that sensitive data, such as passwords and tokens, are transmitted securely.

---

## Additional Notes

- **Versioning:**  
  This API is currently unversioned. Future versions may be introduced; ensure that you refer to the correct version when integrating.
- **Rate Limiting:**  
  Consider implementing rate limiting to protect the API from abuse.
- **Documentation Updates:**  
  This document will be updated as new endpoints are added or existing endpoints are modified.
- **Support:**  
  For further assistance, contact the API support team.

---

This README.md file provides a comprehensive, detailed overview of all API endpoints and functions. It serves as a complete guide for developers to integrate with the API effectively.
```