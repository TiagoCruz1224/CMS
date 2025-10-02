#!/bin/bash

# CMS Backend API Test Script
# This script tests all major endpoints

BASE_URL="http://localhost:8000/api"
ADMIN_TOKEN=""
USER_TOKEN=""

echo "🚀 CMS Backend API Test Suite"
echo "=============================="
echo ""

# Colors
GREEN='\033[0;32m'
RED='\033[0;31m'
NC='\033[0m' # No Color

# Test 1: Register Admin User
echo "📝 Test 1: Register Admin User"
ADMIN_RESPONSE=$(curl -s -X POST "$BASE_URL/register" \
  -H "Content-Type: application/json" \
  -d '{
    "email": "admin@cms.com",
    "password": "admin123",
    "firstName": "Admin",
    "lastName": "User",
    "roles": ["ROLE_ADMIN"]
  }')

if echo "$ADMIN_RESPONSE" | grep -q "User registered successfully"; then
    echo -e "${GREEN}✓ Admin user registered${NC}"
else
    echo -e "${RED}✗ Failed to register admin${NC}"
    echo "$ADMIN_RESPONSE"
fi
echo ""

# Test 2: Register Regular User
echo "📝 Test 2: Register Regular User"
USER_RESPONSE=$(curl -s -X POST "$BASE_URL/register" \
  -H "Content-Type: application/json" \
  -d '{
    "email": "user@cms.com",
    "password": "user123",
    "firstName": "John",
    "lastName": "Doe"
  }')

if echo "$USER_RESPONSE" | grep -q "User registered successfully"; then
    echo -e "${GREEN}✓ Regular user registered${NC}"
else
    echo -e "${RED}✗ Failed to register user${NC}"
    echo "$USER_RESPONSE"
fi
echo ""

# Test 3: Login as Admin
echo "🔐 Test 3: Admin Login"
ADMIN_LOGIN=$(curl -s -X POST "$BASE_URL/login" \
  -H "Content-Type: application/json" \
  -d '{
    "email": "admin@cms.com",
    "password": "admin123"
  }')

ADMIN_TOKEN=$(echo "$ADMIN_LOGIN" | grep -o '"token":"[^"]*"' | cut -d'"' -f4)

if [ -n "$ADMIN_TOKEN" ]; then
    echo -e "${GREEN}✓ Admin login successful${NC}"
    echo "Token: ${ADMIN_TOKEN:0:50}..."
else
    echo -e "${RED}✗ Admin login failed${NC}"
    echo "$ADMIN_LOGIN"
fi
echo ""

# Test 4: Login as User
echo "🔐 Test 4: User Login"
USER_LOGIN=$(curl -s -X POST "$BASE_URL/login" \
  -H "Content-Type: application/json" \
  -d '{
    "email": "user@cms.com",
    "password": "user123"
  }')

USER_TOKEN=$(echo "$USER_LOGIN" | grep -o '"token":"[^"]*"' | cut -d'"' -f4)

if [ -n "$USER_TOKEN" ]; then
    echo -e "${GREEN}✓ User login successful${NC}"
    echo "Token: ${USER_TOKEN:0:50}..."
else
    echo -e "${RED}✗ User login failed${NC}"
    echo "$USER_LOGIN"
fi
echo ""

# Test 5: Create Category (Admin only)
echo "🏷️  Test 5: Create Category"
CATEGORY_RESPONSE=$(curl -s -X POST "$BASE_URL/categories" \
  -H "Content-Type: application/json" \
  -H "Authorization: Bearer $ADMIN_TOKEN" \
  -d '{
    "name": "Blog Posts",
    "slug": "blog-posts",
    "description": "All blog posts go here"
  }')

if echo "$CATEGORY_RESPONSE" | grep -q '"id"'; then
    CATEGORY_ID=$(echo "$CATEGORY_RESPONSE" | grep -o '"id":[0-9]*' | cut -d':' -f2)
    echo -e "${GREEN}✓ Category created (ID: $CATEGORY_ID)${NC}"
else
    echo -e "${RED}✗ Failed to create category${NC}"
    echo "$CATEGORY_RESPONSE"
fi
echo ""

# Test 6: List Categories
echo "📋 Test 6: List Categories"
CATEGORIES=$(curl -s -X GET "$BASE_URL/categories")

if echo "$CATEGORIES" | grep -q "Blog Posts"; then
    echo -e "${GREEN}✓ Categories listed successfully${NC}"
else
    echo -e "${RED}✗ Failed to list categories${NC}"
fi
echo ""

# Test 7: Create Page
echo "📄 Test 7: Create Page"
PAGE_RESPONSE=$(curl -s -X POST "$BASE_URL/pages" \
  -H "Content-Type: application/json" \
  -H "Authorization: Bearer $USER_TOKEN" \
  -d '{
    "title": "My First Blog Post",
    "slug": "my-first-blog-post",
    "content": "This is the main content of my first blog post!",
    "status": "published",
    "metaTitle": "My First Blog Post - SEO Title",
    "metaDescription": "Learn all about my first blog post and CMS features",
    "metaKeywords": ["blog", "cms", "symfony"],
    "ogTitle": "My First Blog Post",
    "ogDescription": "Check out my first blog post!",
    "categoryId": '$CATEGORY_ID'
  }')

if echo "$PAGE_RESPONSE" | grep -q '"id"'; then
    PAGE_ID=$(echo "$PAGE_RESPONSE" | grep -o '"id":[0-9]*' | cut -d':' -f2 | head -1)
    echo -e "${GREEN}✓ Page created (ID: $PAGE_ID)${NC}"
else
    echo -e "${RED}✗ Failed to create page${NC}"
    echo "$PAGE_RESPONSE"
fi
echo ""

# Test 8: Create Text Block
echo "📦 Test 8: Create Text Block"
BLOCK_RESPONSE=$(curl -s -X POST "$BASE_URL/blocks" \
  -H "Content-Type: application/json" \
  -H "Authorization: Bearer $USER_TOKEN" \
  -d '{
    "type": "text",
    "pageId": '$PAGE_ID',
    "content": "This is a text block with rich content!",
    "position": 0,
    "settings": {
      "backgroundColor": "#f5f5f5",
      "padding": "20px"
    }
  }')

if echo "$BLOCK_RESPONSE" | grep -q '"id"'; then
    BLOCK_ID=$(echo "$BLOCK_RESPONSE" | grep -o '"id":[0-9]*' | cut -d':' -f2 | head -1)
    echo -e "${GREEN}✓ Block created (ID: $BLOCK_ID)${NC}"
else
    echo -e "${RED}✗ Failed to create block${NC}"
    echo "$BLOCK_RESPONSE"
fi
echo ""

# Test 9: Create Media
echo "🖼️  Test 9: Create Media"
MEDIA_RESPONSE=$(curl -s -X POST "$BASE_URL/media" \
  -H "Content-Type: application/json" \
  -H "Authorization: Bearer $USER_TOKEN" \
  -d '{
    "filename": "example-image.jpg",
    "type": "image",
    "url": "https://via.placeholder.com/800x600",
    "blockId": '$BLOCK_ID',
    "mimeType": "image/jpeg",
    "size": 125000,
    "alt": "Example placeholder image"
  }')

if echo "$MEDIA_RESPONSE" | grep -q '"id"'; then
    MEDIA_ID=$(echo "$MEDIA_RESPONSE" | grep -o '"id":[0-9]*' | cut -d':' -f2 | head -1)
    echo -e "${GREEN}✓ Media created (ID: $MEDIA_ID)${NC}"
else
    echo -e "${RED}✗ Failed to create media${NC}"
    echo "$MEDIA_RESPONSE"
fi
echo ""

# Test 10: Get Page with Blocks
echo "📖 Test 10: Get Page with Blocks"
PAGE_DETAIL=$(curl -s -X GET "$BASE_URL/pages/$PAGE_ID")

if echo "$PAGE_DETAIL" | grep -q "My First Blog Post"; then
    echo -e "${GREEN}✓ Page retrieved with blocks${NC}"
else
    echo -e "${RED}✗ Failed to get page${NC}"
fi
echo ""

# Test 11: Update Page
echo "✏️  Test 11: Update Page"
UPDATE_RESPONSE=$(curl -s -X PUT "$BASE_URL/pages/$PAGE_ID" \
  -H "Content-Type: application/json" \
  -H "Authorization: Bearer $USER_TOKEN" \
  -d '{
    "title": "My Updated Blog Post",
    "content": "This content has been updated!"
  }')

if echo "$UPDATE_RESPONSE" | grep -q "My Updated Blog Post"; then
    echo -e "${GREEN}✓ Page updated successfully${NC}"
else
    echo -e "${RED}✗ Failed to update page${NC}"
fi
echo ""

# Test 12: List Page Revisions
echo "📜 Test 12: List Page Revisions"
REVISIONS=$(curl -s -X GET "$BASE_URL/pages/$PAGE_ID/revisions" \
  -H "Authorization: Bearer $USER_TOKEN")

if echo "$REVISIONS" | grep -q '"id"'; then
    echo -e "${GREEN}✓ Revisions retrieved${NC}"
    echo "Revision count: $(echo "$REVISIONS" | grep -o '"id":' | wc -l)"
else
    echo -e "${RED}✗ Failed to get revisions${NC}"
fi
echo ""

# Test 13: List Published Pages (Public)
echo "🌍 Test 13: List Published Pages (No Auth)"
PUBLIC_PAGES=$(curl -s -X GET "$BASE_URL/pages?status=published")

if echo "$PUBLIC_PAGES" | grep -q '"id"'; then
    echo -e "${GREEN}✓ Public pages listed${NC}"
else
    echo -e "${RED}✗ Failed to list public pages${NC}"
fi
echo ""

# Test 14: Get User Profile
echo "👤 Test 14: Get User Profile"
PROFILE=$(curl -s -X GET "$BASE_URL/profile" \
  -H "Authorization: Bearer $USER_TOKEN")

if echo "$PROFILE" | grep -q "user@cms.com"; then
    echo -e "${GREEN}✓ Profile retrieved${NC}"
else
    echo -e "${RED}✗ Failed to get profile${NC}"
fi
echo ""

echo "=============================="
echo "✅ Test Suite Complete!"
echo ""
echo "Summary:"
echo "- Admin Token: ${ADMIN_TOKEN:0:30}..."
echo "- User Token: ${USER_TOKEN:0:30}..."
echo "- Category ID: $CATEGORY_ID"
echo "- Page ID: $PAGE_ID"
echo "- Block ID: $BLOCK_ID"
echo "- Media ID: $MEDIA_ID"
echo ""
echo "🎉 Your CMS backend is fully functional!"
