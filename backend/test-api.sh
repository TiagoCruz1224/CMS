#!/bin/bash

# CMS Backend API smoke test
# Runs against a live server (BASE_URL) and promotes its admin with bin/console,
# so run it from a machine that can reach the same database as the server.
# Usage: ./test-api.sh   (optional: BASE_URL=http://localhost:8001/api PHP="php -d extension=sodium")

cd "$(dirname "$0")" || exit 1

BASE_URL="${BASE_URL:-http://localhost:8000/api}"
PHP="${PHP:-php}"
# Unique accounts and slugs per run, so the script can be re-run on the same database
RUN_ID=$(date +%s)
ADMIN_EMAIL="admin-$RUN_ID@example.com"
USER_EMAIL="user-$RUN_ID@example.com"
TEST_PASSWORD="ChangeMe-$RUN_ID"
ADMIN_TOKEN=""
USER_TOKEN=""
FAILED=0

echo "🚀 CMS Backend API Test Suite"
echo "=============================="
echo ""

# Colors
GREEN='\033[0;32m'
RED='\033[0;31m'
NC='\033[0m' # No Color

fail() {
    echo -e "${RED}✗ $1${NC}"
    FAILED=$((FAILED + 1))
}

# Test 1: Register Admin User (registration always creates ROLE_USER accounts)
echo "📝 Test 1: Register Admin User"
ADMIN_RESPONSE=$(curl -s -X POST "$BASE_URL/register" \
  -H "Content-Type: application/json" \
  -d '{
    "email": "'"$ADMIN_EMAIL"'",
    "password": "'"$TEST_PASSWORD"'",
    "firstName": "Admin",
    "lastName": "User"
  }')

if echo "$ADMIN_RESPONSE" | grep -q "User registered successfully"; then
    echo -e "${GREEN}✓ Admin user registered${NC}"
else
    fail "Failed to register admin"
    echo "$ADMIN_RESPONSE"
fi
echo ""

# Test 2: Promote Admin User (roles are never taken from API requests)
echo "🛡️  Test 2: Promote Admin User"
if $PHP bin/console dbal:run-sql "UPDATE user SET roles = '[\"ROLE_ADMIN\"]' WHERE email = '$ADMIN_EMAIL'" > /dev/null; then
    echo -e "${GREEN}✓ Admin user promoted to ROLE_ADMIN${NC}"
else
    fail "Failed to promote admin (is the database in .env reachable?)"
fi
echo ""

# Test 3: Register Regular User
echo "📝 Test 3: Register Regular User"
USER_RESPONSE=$(curl -s -X POST "$BASE_URL/register" \
  -H "Content-Type: application/json" \
  -d '{
    "email": "'"$USER_EMAIL"'",
    "password": "'"$TEST_PASSWORD"'",
    "firstName": "John",
    "lastName": "Doe"
  }')

if echo "$USER_RESPONSE" | grep -q "User registered successfully"; then
    echo -e "${GREEN}✓ Regular user registered${NC}"
else
    fail "Failed to register user"
    echo "$USER_RESPONSE"
fi
echo ""

# Test 4: Login as Admin
echo "🔐 Test 4: Admin Login"
ADMIN_LOGIN=$(curl -s -X POST "$BASE_URL/login" \
  -H "Content-Type: application/json" \
  -d '{
    "email": "'"$ADMIN_EMAIL"'",
    "password": "'"$TEST_PASSWORD"'"
  }')

ADMIN_TOKEN=$(echo "$ADMIN_LOGIN" | grep -o '"token":"[^"]*"' | cut -d'"' -f4)

if [ -n "$ADMIN_TOKEN" ]; then
    echo -e "${GREEN}✓ Admin login successful${NC}"
else
    fail "Admin login failed"
    echo "$ADMIN_LOGIN"
fi
echo ""

# Test 5: Login as User
echo "🔐 Test 5: User Login"
USER_LOGIN=$(curl -s -X POST "$BASE_URL/login" \
  -H "Content-Type: application/json" \
  -d '{
    "email": "'"$USER_EMAIL"'",
    "password": "'"$TEST_PASSWORD"'"
  }')

USER_TOKEN=$(echo "$USER_LOGIN" | grep -o '"token":"[^"]*"' | cut -d'"' -f4)

if [ -n "$USER_TOKEN" ]; then
    echo -e "${GREEN}✓ User login successful${NC}"
else
    fail "User login failed"
    echo "$USER_LOGIN"
fi
echo ""

# Test 6: Create Category (Admin only)
echo "🏷️  Test 6: Create Category"
CATEGORY_RESPONSE=$(curl -s -X POST "$BASE_URL/categories" \
  -H "Content-Type: application/json" \
  -H "Authorization: Bearer $ADMIN_TOKEN" \
  -d '{
    "name": "Blog Posts '"$RUN_ID"'",
    "slug": "blog-posts-'"$RUN_ID"'",
    "description": "All blog posts go here"
  }')

if echo "$CATEGORY_RESPONSE" | grep -q '"id"'; then
    CATEGORY_ID=$(echo "$CATEGORY_RESPONSE" | grep -o '"id":[0-9]*' | cut -d':' -f2)
    echo -e "${GREEN}✓ Category created (ID: $CATEGORY_ID)${NC}"
else
    fail "Failed to create category"
    echo "$CATEGORY_RESPONSE"
fi
echo ""

# Test 7: List Categories
echo "📋 Test 7: List Categories"
CATEGORIES=$(curl -s -X GET "$BASE_URL/categories")

if echo "$CATEGORIES" | grep -q "Blog Posts $RUN_ID"; then
    echo -e "${GREEN}✓ Categories listed successfully${NC}"
else
    fail "Failed to list categories"
fi
echo ""

# Test 8: Create Page
echo "📄 Test 8: Create Page"
PAGE_RESPONSE=$(curl -s -X POST "$BASE_URL/pages" \
  -H "Content-Type: application/json" \
  -H "Authorization: Bearer $USER_TOKEN" \
  -d '{
    "title": "My First Blog Post",
    "slug": "my-first-blog-post-'"$RUN_ID"'",
    "content": "This is the main content of my first blog post!",
    "status": "published",
    "metaTitle": "My First Blog Post - SEO Title",
    "metaDescription": "Learn all about my first blog post and CMS features",
    "metaKeywords": ["blog", "cms", "symfony"],
    "ogTitle": "My First Blog Post",
    "ogDescription": "Check out my first blog post!",
    "categoryId": '"${CATEGORY_ID:-null}"'
  }')

if echo "$PAGE_RESPONSE" | grep -q '"id"'; then
    PAGE_ID=$(echo "$PAGE_RESPONSE" | grep -o '"id":[0-9]*' | cut -d':' -f2 | head -1)
    echo -e "${GREEN}✓ Page created (ID: $PAGE_ID)${NC}"
else
    fail "Failed to create page"
    echo "$PAGE_RESPONSE"
fi
echo ""

# Test 9: Create Text Block
echo "📦 Test 9: Create Text Block"
BLOCK_RESPONSE=$(curl -s -X POST "$BASE_URL/blocks" \
  -H "Content-Type: application/json" \
  -H "Authorization: Bearer $USER_TOKEN" \
  -d '{
    "type": "text",
    "pageId": '"${PAGE_ID:-0}"',
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
    fail "Failed to create block"
    echo "$BLOCK_RESPONSE"
fi
echo ""

# Test 10: Create Media
echo "🖼️  Test 10: Create Media"
MEDIA_RESPONSE=$(curl -s -X POST "$BASE_URL/media" \
  -H "Content-Type: application/json" \
  -H "Authorization: Bearer $USER_TOKEN" \
  -d '{
    "filename": "example-image.jpg",
    "type": "image",
    "url": "https://via.placeholder.com/800x600",
    "blockId": '"${BLOCK_ID:-0}"',
    "mimeType": "image/jpeg",
    "size": 125000,
    "alt": "Example placeholder image"
  }')

if echo "$MEDIA_RESPONSE" | grep -q '"id"'; then
    MEDIA_ID=$(echo "$MEDIA_RESPONSE" | grep -o '"id":[0-9]*' | cut -d':' -f2 | head -1)
    echo -e "${GREEN}✓ Media created (ID: $MEDIA_ID)${NC}"
else
    fail "Failed to create media"
    echo "$MEDIA_RESPONSE"
fi
echo ""

# Test 11: Get Page with Blocks
echo "📖 Test 11: Get Page with Blocks"
PAGE_DETAIL=$(curl -s -X GET "$BASE_URL/pages/$PAGE_ID")

if echo "$PAGE_DETAIL" | grep -q "My First Blog Post"; then
    echo -e "${GREEN}✓ Page retrieved with blocks${NC}"
else
    fail "Failed to get page"
fi
echo ""

# Test 12: Update Page
echo "✏️  Test 12: Update Page"
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
    fail "Failed to update page"
fi
echo ""

# Test 13: List Page Revisions
echo "📜 Test 13: List Page Revisions"
REVISIONS=$(curl -s -X GET "$BASE_URL/pages/$PAGE_ID/revisions" \
  -H "Authorization: Bearer $USER_TOKEN")

if echo "$REVISIONS" | grep -q '"id"'; then
    echo -e "${GREEN}✓ Revisions retrieved${NC}"
    echo "Revision count: $(echo "$REVISIONS" | grep -o '"id":' | wc -l)"
else
    fail "Failed to get revisions"
fi
echo ""

# Test 14: List Published Pages (Public)
echo "🌍 Test 14: List Published Pages (No Auth)"
PUBLIC_PAGES=$(curl -s -X GET "$BASE_URL/pages?status=published")

if echo "$PUBLIC_PAGES" | grep -q '"id"'; then
    echo -e "${GREEN}✓ Public pages listed${NC}"
else
    fail "Failed to list public pages"
fi
echo ""

# Test 15: Get User Profile
echo "👤 Test 15: Get User Profile"
PROFILE=$(curl -s -X GET "$BASE_URL/profile" \
  -H "Authorization: Bearer $USER_TOKEN")

if echo "$PROFILE" | grep -q "$USER_EMAIL"; then
    echo -e "${GREEN}✓ Profile retrieved${NC}"
else
    fail "Failed to get profile"
fi
echo ""

echo "=============================="
echo "Summary:"
echo "- Admin: $ADMIN_EMAIL"
echo "- User: $USER_EMAIL"
echo "- Category ID: $CATEGORY_ID"
echo "- Page ID: $PAGE_ID"
echo "- Block ID: $BLOCK_ID"
echo "- Media ID: $MEDIA_ID"
echo ""

if [ "$FAILED" -gt 0 ]; then
    echo -e "${RED}✗ $FAILED check(s) failed${NC}"
    exit 1
fi
echo -e "${GREEN}✅ All checks passed${NC}"
