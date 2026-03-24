# System Architecture

## Current State
The existing system is comprised of three parts located in the same development environment:

1. **Legacy Native PHP System** (`c:/xampp/htdocs/fleetriteAPI/public/fleetrite_nv_latest_version`): This is the current production source of truth. It handles all business logic, direct database connections, complex tracking logic, and user interfaces inside a monolithic structure.
2. **Laravel Backend Project** (`c:/xampp/htdocs/fleetriteAPI`): A modern Laravel 12 project that currently serves as the foundation for the new API. It handles authentication using Laravel Sanctum, providing endpoints like `/api/login` and `/api/logout`, and supports both legacy MD5 and modern bcrypt password models for the `GsUser` instance.
3. **Vue Frontend Project** (`c:/xampp/htdocs/fleetrite-web`): The modern replacement for the legacy UI. It integrates with the Laravel backend for authentication and contains initial foundations like a tracking view, dashboard maps, and unified styles.

## Target State Architecture
The target state is a full decoupling of the frontend and backend, entirely eliminating the Native PHP application:
- **Client Tier**: A Single Page Application (SPA) built with Vue.js 3, consuming JSON APIs.
- **API Tier**: The Laravel 12 API handling routing, permissions, validations, business logic, and communication with external GPS devices/TCPIP servers (if applicable).
- **Data Tier**: The existing MySQL/MariaDB database, structurally preserved but accessed via Laravel Eloquent ORM instead of raw PHP MySQLi/PDO queries.
