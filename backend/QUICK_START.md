# 🚀 CMS Backend - Quick Start Guide

## Prerequisites
- PHP 8.1+
- MySQL 8.0+
- Composer
- Symfony CLI (optional)

---

## 📦 Installation Steps

### 1. Install Dependencies
```bash
cd /home/tiago/CMS/backend
composer install
```

### 2. Configure Environment
Your `.env.local` is already configured:
```bash
DATABASE_URL="mysql://tiago:12345@127.0.0.1:3306/cms_db?serverVersion=8.0"
```

### 3. Database Setup
```bash
# Create database (already done)
php bin/console doctrine:database:create

# Run migrations (already done)
php bin/console doctrine:migrations:migrate
```

### 4. Generate JWT Keys (already done)
```bash
php bin/console lexik:jwt:generate-keypair
```

---

## 🎮 Running the Application

### Start Backend Server
```bash
cd /home/tiago/CMS/backend
php -S localhost:8000 -t public
```

### Start Frontend (Vue)
```bash
cd /home/tiago/CMS/frontend
yarn dev
```

---

## 🧪 Testing the API

### Option 1: Use Test Script
```bash
cd /home/tiago/CMS/backend
./test-api.sh
```

### Option 2: Manual Testing

#### 1. Register User
```bash
curl -X POST http://localhost:8000/api/register \
  -H "Content-Type: application/json" \
  -d '{
    "email": "admin@example.com",
    "password": "password123",
    "firstName": "Admin",
    "lastName": "User",
    "roles": ["ROLE_ADMIN"]
  }'
```

#### 2. Login
```bash
curl -X POST http://localhost:8000/api/login \
  -H "Content-Type: application/json" \
  -d '{
    "email": "admin@example.com",
    "password": "password123"
  }'
```

**Response:** Copy the `token` value

#### 3. Create Page (Replace YOUR_TOKEN)
```bash
curl -X POST http://localhost:8000/api/pages \
  -H "Content-Type: application/json" \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -d '{
    "title": "My First Page",
    "slug": "my-first-page",
    "content": "This is my first page content",
    "status": "published",
    "metaTitle": "My First Page - CMS",
    "metaDescription": "This is an awesome first page"
  }'
```

#### 4. Get Pages (Public - No Auth)
```bash
curl http://localhost:8000/api/pages
```

---

## 📚 API Endpoints Summary

### Authentication
| Method | Endpoint | Auth | Description |
|--------|----------|------|-------------|
| POST | `/api/register` | No | Register new user |
| POST | `/api/login` | No | Login and get JWT token |
| GET | `/api/profile` | Yes | Get current user profile |
| PUT | `/api/profile` | Yes | Update profile |

### Pages
| Method | Endpoint | Auth | Description |
|--------|----------|------|-------------|
| GET | `/api/pages` | No | List all pages (paginated) |
| GET | `/api/pages/{id}` | No | Get single page |
| POST | `/api/pages` | Yes | Create page |
| PUT | `/api/pages/{id}` | Yes | Update page |
| DELETE | `/api/pages/{id}` | Yes | Delete page |

### Page Revisions
| Method | Endpoint | Auth | Description |
|--------|----------|------|-------------|
| GET | `/api/pages/{id}/revisions` | Yes | List page revisions |
| GET | `/api/pages/{id}/revisions/{revisionId}` | Yes | Get revision |
| POST | `/api/pages/{id}/revisions/{revisionId}/restore` | Yes | Restore revision |

### Blocks
| Method | Endpoint | Auth | Description |
|--------|----------|------|-------------|
| GET | `/api/blocks/{id}` | Yes | Get block |
| POST | `/api/blocks` | Yes | Create block |
| PUT | `/api/blocks/{id}` | Yes | Update block |
| DELETE | `/api/blocks/{id}` | Yes | Delete block |

### Media
| Method | Endpoint | Auth | Description |
|--------|----------|------|-------------|
| GET | `/api/media` | Yes | List all media |
| GET | `/api/media/{id}` | Yes | Get media |
| POST | `/api/media` | Yes | Create media |
| PUT | `/api/media/{id}` | Yes | Update media |
| DELETE | `/api/media/{id}` | Yes | Delete media |

### Categories
| Method | Endpoint | Auth | Description |
|--------|----------|------|-------------|
| GET | `/api/categories` | No | List all categories |
| GET | `/api/categories/{id}` | No | Get category |
| POST | `/api/categories` | Admin | Create category |
| PUT | `/api/categories/{id}` | Admin | Update category |
| DELETE | `/api/categories/{id}` | Admin | Delete category |

---

## 🔑 Authentication Flow

### 1. Login Request
```json
POST /api/login
{
  "email": "user@example.com",
  "password": "password123"
}
```

### 2. Response
```json
{
  "token": "eyJ0eXAiOiJKV1QiLCJhbGc..."
}
```

### 3. Use Token in Requests
```
Authorization: Bearer eyJ0eXAiOiJKV1QiLCJhbGc...
```

---

## 🎨 Frontend Integration (Vue 3)

### Install Axios
```bash
cd /home/tiago/CMS/frontend
yarn add axios
```

### Create API Service
```javascript
// src/services/api.js
import axios from 'axios'

const api = axios.create({
  baseURL: 'http://localhost:8000/api',
  headers: {
    'Content-Type': 'application/json'
  }
})

// Add token to requests
api.interceptors.request.use(config => {
  const token = localStorage.getItem('jwt')
  if (token) {
    config.headers.Authorization = `Bearer ${token}`
  }
  return config
})

export default api
```

### Usage in Components
```javascript
// Login
import api from '@/services/api'

async function login(email, password) {
  const { data } = await api.post('/login', { email, password })
  localStorage.setItem('jwt', data.token)
}

// Get Pages
async function getPages() {
  const { data } = await api.get('/pages')
  return data.data
}

// Create Page
async function createPage(pageData) {
  const { data } = await api.post('/pages', pageData)
  return data
}
```

---

## 📝 Page Example with All Features

```json
{
  "title": "Complete Page Example",
  "slug": "complete-example",
  "content": "This is the main content",
  "status": "published",
  "metaTitle": "Complete Example | My CMS",
  "metaDescription": "This is a complete example page with all features",
  "metaKeywords": ["example", "cms", "complete"],
  "ogTags": {
    "og:title": "Complete Example",
    "og:description": "Amazing page example",
    "og:image": "https://example.com/image.jpg"
  },
  "canonicalUrl": "https://example.com/complete-example",
  "isIndexable": true,
  "isFeatured": true,
  "publishAt": "2025-10-02T12:00:00+00:00",
  "categoryId": 1
}
```

---

## 🛠️ Common Tasks

### Create Admin User
```bash
curl -X POST http://localhost:8000/api/register \
  -H "Content-Type: application/json" \
  -d '{
    "email": "admin@cms.local",
    "password": "admin123",
    "roles": ["ROLE_ADMIN"]
  }'
```

### Create Category (Admin Only)
```bash
curl -X POST http://localhost:8000/api/categories \
  -H "Content-Type: application/json" \
  -H "Authorization: Bearer YOUR_ADMIN_TOKEN" \
  -d '{
    "name": "Blog",
    "slug": "blog",
    "description": "Blog posts"
  }'
```

### Add Block to Page
```bash
curl -X POST http://localhost:8000/api/blocks \
  -H "Content-Type: application/json" \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -d '{
    "type": "text",
    "content": "This is a text block",
    "pageId": 1,
    "position": 0
  }'
```

### View Page Revisions
```bash
curl http://localhost:8000/api/pages/1/revisions \
  -H "Authorization: Bearer YOUR_TOKEN"
```

---

## 🐛 Troubleshooting

### Database Connection Error
Check `.env.local`:
```bash
DATABASE_URL="mysql://tiago:12345@127.0.0.1:3306/cms_db?serverVersion=8.0"
```

### JWT Token Error
Regenerate keys:
```bash
php bin/console lexik:jwt:generate-keypair --overwrite
```

### CORS Error
Update `config/packages/nelmio_cors.yaml`:
```yaml
nelmio_cors:
    defaults:
        origin_regex: true
        allow_origin: ['%env(CORS_ALLOW_ORIGIN)%']
        allow_methods: ['GET', 'OPTIONS', 'POST', 'PUT', 'PATCH', 'DELETE']
        allow_headers: ['Content-Type', 'Authorization']
```

---

## 📊 Database Structure

Tables created:
- `user` - Users and authentication
- `page` - Pages with SEO fields
- `page_revision` - Version history
- `block` - Content blocks
- `media` - Media files
- `category` - Categories
- `messenger_messages` - Queue messages

---

## 🚀 Production Deployment

### 1. Set Production Environment
```bash
APP_ENV=prod
APP_DEBUG=0
```

### 2. Update Database URL
```bash
DATABASE_URL="mysql://prod_user:prod_pass@prod_host:3306/cms_db"
```

### 3. Update CORS
```bash
CORS_ALLOW_ORIGIN='^https://yourdomain\.com$'
```

### 4. Cache Clear
```bash
php bin/console cache:clear --env=prod
```

### 5. Run Migrations
```bash
php bin/console doctrine:migrations:migrate --no-interaction
```

---

## ✅ You're All Set!

Your CMS backend is ready to use with:
- ✅ Complete CRUD operations
- ✅ JWT authentication
- ✅ SEO optimization
- ✅ Version control
- ✅ Role-based access
- ✅ Clean architecture

**Next:** Connect your Vue 3 frontend! 🎉
