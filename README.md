# Book Stack Calculator API

**Author:** Toni Naumoski

A Laravel-based REST API for calculating visible book stacks in a grid from all four directions (top, bottom, left, right). This application solves the classic algorithmic problem where you need to determine how many stacks are visible when viewing a grid of book stacks from different perspectives.

## Features

- **User Authentication**: Secure API with Laravel Sanctum authentication
- **Grid Calculation**: Calculate visible stacks from all 4 directions
- **Persistent Storage**: Save and retrieve calculation history
- **RESTful API**: Clean, well-documented endpoints
- **Input Validation**: Comprehensive validation for grid data
- **Pagination**: Efficient pagination for calculation history

## API Endpoints

### Authentication
- `POST /api/register` - Register a new user
- `POST /api/login` - Login and get access token

### Calculations (Requires Authentication)
- `POST /api/calculations` - Calculate visible stacks for a grid
- `GET /api/calculations` - Get user's calculation history (paginated)
- `GET /api/calculations/{id}` - Get specific calculation details
- `DELETE /api/calculations/{id}` - Delete a calculation

### Health Check
- `GET /api/health` - API health status

## Algorithm Overview

The core algorithm calculates visible stacks by scanning the grid from all four directions:

1. **From Top**: For each column, scan downward, counting stacks taller than previous ones
2. **From Bottom**: For each column, scan upward, counting stacks taller than previous ones
3. **From Left**: For each row, scan rightward, counting stacks taller than previous ones
4. **From Right**: For each row, scan leftward, counting stacks taller than previous ones

Stacks are only counted once, even if visible from multiple directions.

## Installation

1. Clone the repository
2. Install dependencies:
   ```bash
   composer install
   npm install
   ```
3. Copy environment file:
   ```bash
   cp .env.example .env
   ```
4. Generate application key:
   ```bash
   php artisan key:generate
   ```
5. Run migrations:
   ```bash
   php artisan migrate
   ```
6. Build assets:
   ```bash
   npm run build
   ```

## Usage

### Development Server
```bash
composer run dev
```

This starts the Laravel server, queue worker, and Vite dev server concurrently.

### Testing
```bash
composer run test
```

## Request/Response Examples

### Calculate Visible Stacks
```bash
POST /api/calculations
Authorization: Bearer {token}
Content-Type: application/json

{
  "grid_size": 3,
  "grid_data": [
    [1, 2, 3],
    [4, 5, 6],
    [7, 8, 9]
  ]
}
```

Response:
```json
{
  "success": true,
  "message": "Calculation completed successfully",
  "data": {
    "id": 1,
    "user_id": 1,
    "grid_size": 3,
    "grid_data": [[1,2,3],[4,5,6],[7,8,9]],
    "visible_stacks": 9,
    "visibility_details": {...},
    "created_at": "2025-10-17T21:49:41.000000Z"
  }
}
```

## Technologies Used

- **Laravel 12**: PHP framework
- **Laravel Sanctum**: API authentication
- **MySQL/SQLite**: Database
- **Composer**: PHP dependency management
- **NPM**: Node.js dependency management
- **Vite**: Frontend build tool

## Project Structure

```
app/
├── Http/Controllers/
│   ├── AuthController.php
│   └── BookCalculationController.php
├── Models/
│   ├── BookCalculation.php
│   └── User.php
database/
├── migrations/
│   └── 2025_10_16_165029_create_book_calculations_table.php
routes/
└── api.php
```

## License

This project is licensed under the MIT License.
