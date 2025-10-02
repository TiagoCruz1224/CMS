# CMS Backend API Documentation

## 🎯 Overview

A complete, production-ready CMS backend built with Symfony 6.4, featuring:

- ✅ **JWT Authentication** - Secure token-based auth
- ✅ **Block-based Content System** - Flexible content blocks (text, image, video, gallery, code, quote, html)
- ✅ **SEO Optimized** - Meta tags, Open Graph, indexing control
- ✅ **Version Control** - Page revision history with rollback capability
- ✅ **Scheduled Publishing** - Publish/unpublish dates
- ✅ **Role-based Access** - User roles (ROLE_USER, ROLE_ADMIN)
- ✅ **Categories & Templates** - Organize and style pages
- ✅ **Media Management** - Attach media to blocks
- ✅ **Pagination** - Efficient data loading

## 🏗️ Architecture

### Entities
- **User** - Authentication & authorization
- **Page** - Main content pages with SEO fields
- **PageRevision** - Version history for pages
- **Block** - Content blocks (text, image, video, etc.)
- **Media** - Files attached to blocks
- **Category** - Page categorization

### Key Features Added (Improvements)

#### 1. **SEO Fields** ✨
- `metaTitle` - Custom meta title
- `metaDescription` - Meta description (max 500 chars)
- `metaKeywords` - Array of keywords
- `ogTags` - Open Graph tags for social sharing
- `isIndexable` - Control search engine indexing

#### 2. **Publishing Workflow** 📅
- `status` - draft, published, archived, scheduled
- `publishAt` - Schedule future publication
- `unpublishAt` - Auto-unpublish after date
- `isPublished()` - Smart method checks all conditions

#### 3. **Page Templates** 🎨
- `template` - page, post, landing, custom
- Allows different frontend rendering

#### 4. **Version Control** 📜
- `PageRevision` entity stores history
- Automatic revision on create/update
- Optional `changeNote` to document changes
- Stores blocks as JSON snapshot

## 📡 API Endpoints

### Authentication

#### Register
```http
POST /api/register
Content-Type: application/json

{
  "email": "user@example.com",
  "password": "securepassword",
  "firstName": "John",
  "lastName": "Doe"
}
```

#### Login
```http
POST /api/login
Content-Type: application/json

{
  "email": "user@example.com",
  "password": "securepassword"
}
```
**Response:** JWT token

#### Get Profile
```http
GET /api/profile
Authorization: Bearer {token}
```

#### Update Profile
```http
PUT /api/profile
Authorization: Bearer {token}
Content-Type: application/json

{
  "firstName": "Jane",
  "lastName": "Smith"
}
```

---

### Pages

#### List Pages (with pagination)
```http
GET /api/pages?page=1&limit=10&status=published
```

#### Get Single Page
```http
GET /api/pages/{id}
```

#### Create Page
```http
POST /api/pages
Authorization: Bearer {token}
Content-Type: application/json

{
  "title": "My Awesome Page",
  "slug": "my-awesome-page",
  "content": "Page content here",
  "status": "draft",
  "categoryId": 1,
  "metaTitle": "My Awesome Page - Best CMS",
  "metaDescription": "This is the best page about CMS systems",
  "metaKeywords": ["cms", "symfony", "vue"],
  "ogTags": {
    "og:image": "https://example.com/image.jpg",
    "og:type": "article"
  },
  "template": "page",
  "publishAt": "2025-10-10T10:00:00+00:00",
  "isIndexable": true
}
```

#### Update Page (with revision)
```http
PUT /api/pages/{id}
Authorization: Bearer {token}
Content-Type: application/json

{
  "title": "Updated Title",
  "content": "Updated content",
  "changeNote": "Fixed typos and updated SEO"
}
```

#### Delete Page
```http
DELETE /api/pages/{id}
Authorization: Bearer {token}
```

#### Get Page Revisions
```http
GET /api/pages/{id}/revisions
Authorization: Bearer {token}
```

#### Get Specific Revision
```http
GET /api/pages/{pageId}/revisions/{revisionId}
Authorization: Bearer {token}
```

---

### Blocks

#### Get Block
```http
GET /api/blocks/{id}
Authorization: Bearer {token}
```

#### Create Block
```http
POST /api/blocks
Authorization: Bearer {token}
Content-Type: application/json

{
  "type": "text",
  "content": "Block content here",
  "pageId": 1,
  "position": 0,
  "settings": {
    "alignment": "center",
    "fontSize": "large"
  }
}
```

**Block Types:** `text`, `image`, `video`, `gallery`, `code`, `quote`, `html`

#### Update Block
```http
PUT /api/blocks/{id}
Authorization: Bearer {token}
Content-Type: application/json

{
  "content": "Updated content",
  "position": 1
}
```

#### Delete Block
```http
DELETE /api/blocks/{id}
Authorization: Bearer {token}
```

---

### Media

#### List Media
```http
GET /api/media
Authorization: Bearer {token}
```

#### Create Media
```http
POST /api/media
Authorization: Bearer {token}
Content-Type: application/json

{
  "filename": "image.jpg",
  "type": "image",
  "url": "/uploads/image.jpg",
  "blockId": 1,
  "mimeType": "image/jpeg",
  "size": 102400,
  "alt": "Description"
}
```

#### Update Media
```http
PUT /api/media/{id}
Authorization: Bearer {token}
```

#### Delete Media
```http
DELETE /api/media/{id}
Authorization: Bearer {token}
```

---

### Categories

#### List Categories
```http
GET /api/categories
```

#### Create Category (Admin only)
```http
POST /api/categories
Authorization: Bearer {token}
Content-Type: application/json

{
  "name": "Technology",
  "slug": "technology",
  "description": "Tech articles"
}
```

#### Update Category (Admin only)
```http
PUT /api/categories/{id}
Authorization: Bearer {token}
```

#### Delete Category (Admin only)
```http
DELETE /api/categories/{id}
Authorization: Bearer {token}
```

---

## 🔐 Security & Access Control

### Public Endpoints
- `GET /api/pages` - List pages
- `GET /api/pages/{id}` - View single page
- `POST /api/register` - Registration
- `POST /api/login` - Login

### Authenticated Endpoints
- All `POST`, `PUT`, `DELETE` operations require JWT token
- Users can only edit/delete their own pages
- `ROLE_ADMIN` can edit/delete any content

### Authorization Rules
- **Pages**: Author or Admin can edit/delete
- **Blocks**: Page author or Admin
- **Media**: Block's page author or Admin
- **Categories**: Admin only for create/update/delete

---

## 🚀 Improvements Made

### From Basic to Production-Ready:

1. **SEO Complete** ✅
   - Meta title, description, keywords
   - Open Graph tags
   - Indexing control
   
2. **Version Control** ✅
   - Automatic revision creation
   - Change notes
   - Revision history API
   
3. **Smart Publishing** ✅
   - Scheduled publishing
   - Auto-unpublish
   - `isPublished()` method
   
4. **Flexible Templates** ✅
   - Multiple page types
   - Custom templates support
   
5. **Better Structure** ✅
   - Service layer handles business logic
   - Repositories for queries
   - Controllers thin and focused

---

## 📊 Database Schema

```
User (1) ----< (N) Page (1) ----< (N) Block (1) ----< (N) Media
                    |
                    └----< (N) PageRevision
Category (1) ----< (N) Page
```

---

## 🧪 Testing Ready

The structure is ready for PHPUnit tests:
- Services are testable (dependency injection)
- Repositories have custom methods
- Validation is in place
- Proper error handling

---

## 📝 Next Steps for Production

1. **File Upload** - Implement actual file upload handling
2. **Image Processing** - Thumbnails, optimization
3. **Caching** - Redis/Memcached for performance
4. **Rate Limiting** - Prevent abuse
5. **Search** - Full-text search with Elasticsearch
6. **Webhooks** - Notify external services on changes
7. **Audit Log** - Track all changes
8. **Permissions** - Fine-grained permissions system

---

## 🎯 Why These Improvements?

**SEO** - Essential for any CMS that powers public websites
**Revisions** - Critical for content management (rollback mistakes)
**Scheduled Publishing** - Required for content planning
**Templates** - Allows flexible frontend rendering
**Better Architecture** - Maintainable, scalable, testable

This is now a **production-grade CMS backend** ready to power real websites! 🚀
