# Furniture API — Laravel Backend

[![PHP Version](https://img.shields.io/badge/PHP-8.2-purple.svg)](https://www.php.net/downloads.php)
[![Laravel Version](https://img.shields.io/badge/Laravel-11.x-ff6f31.svg)](https://laravel.com/docs/11.x)

Backend API for the Furniture E-commerce platform, built with Laravel.

## 🚀 Features

- **Authentication**: JWT-based user authentication and authorization
- **Products Management**: CRUD for products, categories, and attributes
- **Orders Management**: Order processing and status tracking
- **Search & Filtering**: Advanced product search and filtering
- **Admin Dashboard**: Role-based admin management
- **File Storage**: Image uploads using Cloudinary
- **API Documentation**: Automatic Swagger/OpenAPI documentation

---

## 📋 Prerequisites

- PHP 8.2 or higher
- Composer
- MySQL or MariaDB
- ImageMagick (for image processing)

---

## 🛠 Installation

1. Clone the repository:

```bash
git clone <repository-url>
cd furniture-laravel
```

2. Install dependencies:

```bash
composer install
```

3. Copy `.env.example` to `.env` and configure your database credentials:

```bash
cp .env.example .env
```

Update `.env` with your database and Cloudinary configuration:

```ini
DB_CONNECTION=mysql
DB_HOST=localhost
DB_PORT=3306
DB_DATABASE=furniture
DB_USERNAME=root
DB_PASSWORD=

CLOUDINARY_CLOUD_NAME=your_cloud_name
CLOUDINARY_API_KEY=your_api_key
CLOUDINARY_API_SECRET=your_api_secret
```

4. Generate application key:

```bash
php artisan key:generate
```

5. Run migrations:

```bash
php artisan migrate
```

6. Seed the database with sample data:

```bash
php artisan db:seed --class=AdminSeeder
```

7. Start the development server:

```bash
php artisan serve
```

---

## 🔐 API Authentication

The API uses JWT-based authentication. You need to obtain a token before accessing protected endpoints.

### Register

```http
POST /api/auth/register
```

### Login

```http
POST /api/auth/login
```

### Refresh Token

```http
POST /api/auth/refresh
```

### Logout

```http
POST /api/auth/logout
```

---

## Endpoints

### User Management

```http
GET    /api/users                    # Get all users (admin)
POST   /api/users                    # Create user (admin)
GET    /api/users/{id}               # Get user by ID (admin)
PUT    /api/users/{id}               # Update user (admin)
DELETE /api/users/{id}               # Delete user (admin)
GET    /api/users/me                 # Get current user profile
PUT    /api/users/me                 # Update current user profile
```

### Product Management

```http
GET    /api/products                 # Get all products with optional search/filter
POST   /api/products                 # Create product (admin)
GET    /api/products/{id}            # Get product by ID
PUT    /api/products/{id}            # Update product (admin)
DELETE /api/products/{id}            # Delete product (admin)
```

**Search & Filter Parameters:**
```
GET /api/products?search=sofa&category=living_room&min_price=500&max_price=2000&sort=price_asc
```

### Categories

```http
GET    /api/categories               # Get all categories
POST   /api/categories               # Create category (admin)
GET    /api/categories/{id}          # Get category by ID
PUT    /api/categories/{id}          # Update category (admin)
DELETE /api/categories/{id}          # Delete category (admin)
```

### Attributes

```http
GET    /api/attributes               # Get all attributes
POST   /api/attributes               # Create attribute (admin)
GET    /api/attributes/{id}          # Get attribute by ID
PUT    /api/attributes/{id}          # Update attribute (admin)
DELETE /api/attributes/{id}          # Delete attribute (admin)

# Get product attributes
GET    /api/products/{id}/attributes
```

### Orders

```http
GET    /api/orders                   # Get all orders (admin)
GET    /api/orders/user/{userId}     # Get user's orders (admin)
GET    /api/orders/{id}              # Get order by ID
POST   /api/orders                   # Create order
PUT    /api/orders/{id}              # Update order (admin)
DELETE /api/orders/{id}              # Delete order (admin)
```

### Images

```http
POST   /api/images/upload            # Upload image (supports multipart/form-data)
```

### Dashboard (Admin)

```http
GET    /api/dashboard/stats          # Get system statistics
GET    /api/dashboard/revenue-by-month # Get monthly revenue
GET    /api/dashboard/sales-by-category # Get sales by category
```

---

## 🎨 Admin Roles & Permissions

The system uses role-based access control:

### Roles
- `admin`: Full access to all features
- `seller`: Can manage products and orders
- `customer`: Can only view products and manage their own orders

### Authorization Middleware

```php
// Admin only
Route::middleware(['auth:api', 'role:admin'])

// Seller or Admin
Route::middleware(['auth:api', 'role:admin|seller'])

// Authenticated user (any role)
Route::middleware(['auth:api'])
```

---

## 📁 Project Structure

```
app/
├── Http/Controllers/      # API Controllers
├── Models/                # Eloquent Models
├── Services/              # Business logic
└── Helpers/               # Utility functions

routes/
├── api.php                # API routes
├── admin.php              # Admin routes
└── auth.php               # Authentication routes

config/
├── filesystems.php        # Filesystem configuration
└── jwt.php                # JWT configuration

database/
├── migrations/            # Database migrations
└── seeders/               # Database seeders

public/
├── index.php              # API entry point

.env.example              # Environment template
composer.json            # Dependencies
```

---

## ☁️ Cloudinary Integration

Images are uploaded to Cloudinary using the `CloudinaryStorage` helper.

**Example usage:**

```php
$file = $request->file('image');
$result = Cloudinary::uploadFile($file);
$imageUrl = $result->getSecureUrl();
```

---

## ⚡ Performance Optimization

- **Query Optimization**: Efficient database queries with proper indexing
- **Caching**: Redis-based caching for frequently accessed data
- **Eager Loading**: Prevent N+1 query problems
- **Pagination**: Default pagination for large datasets

---

## 📝 API Documentation

Automatic Swagger/OpenAPI documentation is generated using **Swagger-PHP**.

You can view the documentation at:

```
http://localhost:8000/api/documentation
```

To regenerate the documentation:

```bash
php artisan swagger:generate
```

---

## 🧪 Testing

To run tests:
