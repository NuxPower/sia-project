# JWT vs Sanctum Authentication

## Current Implementation

The KLEMA system currently uses **Laravel Sanctum** for API authentication instead of JWT (JSON Web Tokens) as mentioned in the SRS document.

## Why Sanctum Instead of JWT?

1. **Native Laravel Integration**: Sanctum is Laravel's official first-party package, providing seamless integration with Laravel's authentication system.

2. **Simpler Implementation**: Sanctum uses Laravel's built-in token system, which is simpler to implement and maintain than JWT.

3. **Better Security**: Sanctum tokens are stored in the database and can be revoked easily, providing better control over token lifecycle.

4. **SPA Support**: Sanctum provides built-in support for Single Page Applications (SPAs) and mobile applications.

## Current Authentication Flow

- **Web Routes**: Uses Laravel's session-based authentication
- **API Routes**: Uses Sanctum personal access tokens
- **Token Management**: Tokens can be created, listed, refreshed, and revoked via API endpoints

## API Endpoints for Token Management

- `POST /api/login` - Authenticate and receive token
- `POST /api/logout` - Revoke current token
- `POST /api/token/refresh` - Refresh token
- `GET /api/tokens` - List all user tokens
- `DELETE /api/tokens/{token}` - Revoke specific token

## Migration to JWT (If Required)

If JWT authentication is specifically required, the following changes would be needed:

1. Install a JWT package (e.g., `tymon/jwt-auth`)
2. Update authentication guards in `config/auth.php`
3. Modify `AuthApiController` to use JWT instead of Sanctum
4. Update all API middleware to use JWT guards
5. Update frontend to handle JWT tokens instead of Sanctum tokens

**Note**: This would be a significant refactoring effort and may break existing API integrations.

## Recommendation

Sanctum provides equivalent functionality to JWT for this use case and is better integrated with Laravel. Unless there's a specific requirement for JWT (e.g., microservices architecture, third-party integration requirements), Sanctum is the recommended approach.

