# 🚀 CMS - Content Management System

A modern, full-stack CMS built with **Symfony 6.4** (Backend) and **Vue 3** (Frontend).

![Symfony](https://img.shields.io/badge/Symfony-6.4-000000?style=flat&logo=symfony)
![Vue.js](https://img.shields.io/badge/Vue.js-3.x-4FC08D?style=flat&logo=vue.js)
![PHP](https://img.shields.io/badge/PHP-8.2+-777BB4?style=flat&logo=php)
![MySQL](https://img.shields.io/badge/MySQL-8.0-4479A1?style=flat&logo=mysql)

## ✨ Features

- 📝 **Page Management** - Create, edit, delete pages with SEO fields
- 🧱 **Block System** - Reusable content blocks (text, image, video, HTML, widgets)
- 🖼️ **Media Library** - Upload and manage images, videos, and files
- 🔐 **JWT Authentication** - Secure token-based authentication
- 👥 **User Management** - Role-based access control (Admin, User)
- 📊 **Dashboard** - Statistics and quick actions
- 🌙 **Dark Mode** - Beautiful light/dark theme toggle
- 📱 **Responsive Design** - Mobile, tablet, and desktop support
- 🔄 **Version Control** - Page revision history
- 🏷️ **Categories** - Organize content with categories
- 🎨 **SEO Optimized** - Meta tags, canonical URLs, sitemap support

## 🛠️ Tech Stack

### Backend
- **Framework**: Symfony 6.4 LTS
- **Database**: MySQL 8.0
- **Authentication**: JWT (LexikJWTAuthenticationBundle)
- **ORM**: Doctrine
- **API**: RESTful API with serialization groups

### Frontend
- **Framework**: Vue 3 (Composition API)
- **Build Tool**: Vite 4
- **State Management**: Pinia
- **Routing**: Vue Router 4
- **HTTP Client**: Axios
- **Styling**: Tailwind CSS v3
- **Icons**: Heroicons

## 📋 Prerequisites

- PHP 8.2 or higher with the `sodium` and `pdo_mysql` extensions (plus `pdo_sqlite` to run the tests)
- Composer
- MySQL 8.0 (a local server, or Docker with the bundled `backend/compose.yaml`)
- Node.js 18+ and Yarn
- Symfony CLI (optional but recommended)

## 🚀 Installation

### 1. Clone the Repository

```bash
git clone <your-repo-url>
cd CMS
```

### 2. Backend Setup

```bash
cd backend

# Configure environment (before composer install: its post-install scripts boot Symfony)
cp .env.example .env
# Edit DATABASE_URL, APP_SECRET and JWT_PASSPHRASE in .env (or override them in .env.local)

# Install dependencies (versions pinned by composer.lock)
composer install

# Optional: start MySQL 8.0 with Docker (its defaults match .env.example)
docker compose up -d database

# Generate JWT keys
php bin/console lexik:jwt:generate-keypair

# Create database
php bin/console doctrine:database:create --if-not-exists

# Run migrations
php bin/console doctrine:migrations:migrate --no-interaction

# Start Symfony server
symfony server:start --no-tls
# or
php -S localhost:8000 -t public/
```

### 3. Create an Admin User

Registration always creates a regular user (`ROLE_USER`); roles sent to the API are ignored.
Register through the API, then promote the account from the console (in `backend/`, with the server running):

```bash
curl -X POST http://localhost:8000/api/register \
  -H "Content-Type: application/json" \
  -d '{"email": "admin@example.com", "password": "<choose-a-password>", "firstName": "Admin", "lastName": "User"}'

php bin/console dbal:run-sql "UPDATE user SET roles = '[\"ROLE_ADMIN\"]' WHERE email = 'admin@example.com'"
```

### 4. Frontend Setup

From the repository root:

```bash
cd frontend

# Install dependencies
yarn install

# Start development server
yarn dev
```

## 🔑 Default Credentials

There are no default credentials. Sign in with the admin account created in step 3.

## 📁 Project Structure

```
CMS/
├── backend/              # Symfony Backend
│   ├── config/          # Configuration files
│   ├── migrations/      # Database migrations
│   ├── public/          # Public assets & index.php
│   ├── src/
│   │   ├── Controller/  # API Controllers
│   │   ├── Entity/      # Doctrine Entities
│   │   ├── Repository/  # Custom repositories
│   │   └── Service/     # Business logic
│   └── tests/           # PHPUnit tests
│
├── frontend/            # Vue 3 Frontend
│   ├── public/          # Static assets
│   └── src/
│       ├── components/  # Vue components
│       ├── views/       # Page views
│       ├── stores/      # Pinia stores
│       ├── services/    # API services
│       └── router/      # Vue Router
│
└── README.md
```

## 🔌 API Endpoints

### Authentication
- `POST /api/register` - Register new user
- `POST /api/login` - Login and get JWT token

### Pages
- `GET /api/pages` - List all pages
- `GET /api/pages/{id}` - Get page details
- `POST /api/pages` - Create page
- `PUT /api/pages/{id}` - Update page
- `DELETE /api/pages/{id}` - Delete page

### Blocks
- `GET /api/blocks` - List all blocks
- `POST /api/blocks` - Create block
- `PUT /api/blocks/{id}` - Update block
- `DELETE /api/blocks/{id}` - Delete block

### Media
- `GET /api/media` - List all media
- `POST /api/media` - Upload media
- `DELETE /api/media/{id}` - Delete media

### Categories
- `GET /api/categories` - List all categories
- `POST /api/categories` - Create category
- `PUT /api/categories/{id}` - Update category
- `DELETE /api/categories/{id}` - Delete category

## 🧪 Testing

### Backend Tests
```bash
cd backend
php bin/phpunit
```

The functional tests use a throwaway SQLite database (`backend/.env.test`), so they need no MySQL server.

`backend/test-api.sh` is an end-to-end smoke test for a running server: it registers its own users, promotes
the admin with `bin/console`, and exits with a non-zero status if any check fails.

## 🌐 Production Deployment

### Backend
1. Set `APP_ENV=prod` in `.env`
2. Clear cache: `php bin/console cache:clear`
3. Optimize autoloader: `composer dump-autoload --optimize --no-dev`
4. Set proper file permissions

### Frontend
```bash
cd frontend
yarn build
# Deploy the dist/ folder to your web server
```

## 🔒 Security

- JWT tokens for authentication
- Password hashing with bcrypt
- CORS configured for API access
- Role-based access control
- Input validation and sanitization

## 🤝 Contributing

1. Fork the repository
2. Create your feature branch (`git checkout -b feature/AmazingFeature`)
3. Commit your changes (`git commit -m 'Add some AmazingFeature'`)
4. Push to the branch (`git push origin feature/AmazingFeature`)
5. Open a Pull Request

## 📝 License

This project is licensed under the MIT License.

## 👥 Authors

- Your Name - Initial work

## 🙏 Acknowledgments

- Symfony Framework
- Vue.js Team
- Tailwind CSS
- All contributors

## 📞 Support

For support, email support@example.com or open an issue on GitHub.
